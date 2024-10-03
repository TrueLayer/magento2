<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Math\Random;
use stdClass;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\User\RepositoryInterface as UserRepository;
use TrueLayer\Connect\Helper\AmountHelper;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Exceptions\ApiRequestJsonSerializationException;
use TrueLayer\Exceptions\ApiResponseUnsuccessfulException;
use TrueLayer\Exceptions\InvalidArgumentException;
use TrueLayer\Exceptions\SignerException;
use TrueLayer\Interfaces\Client\ClientInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Interfaces\Payment\PaymentCreatedInterface;

class PaymentCreationService
{
    private ClientFactory $clientFactory;
    private ConfigRepository $configRepository;
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private Random $mathRandom;
    private LogServiceInterface $logger;

    /**
     * @param ClientFactory $clientFactory
     * @param ConfigRepository $configRepository
     * @param TransactionRepository $transactionRepository
     * @param UserRepository $userRepository
     * @param Random $mathRandom
     * @param LogServiceInterface $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        ConfigRepository $configRepository,
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        Random $mathRandom,
        LogServiceInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->configRepository = $configRepository;
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->mathRandom = $mathRandom;
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @return PaymentCreatedInterface
     * @throws ApiRequestJsonSerializationException
     * @throws ApiResponseUnsuccessfulException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws SignerException
     */
    public function createPaymentForOrder(OrderInterface $order): PaymentCreatedInterface
    {
        return $this->createPayment($order, $this->getTransactionByOrder($order), [
            "Magento Order ID" => (string) $order->getEntityId(),
            "Magento Store ID" => (string) $order->getStoreId(),
        ]);
    }

    /**
     * @param CartInterface $quote
     * @return PaymentCreatedInterface
     * @throws ApiRequestJsonSerializationException
     * @throws ApiResponseUnsuccessfulException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws SignerException
     */
    public function createPaymentForQuote(CartInterface $quote): PaymentCreatedInterface
    {
        return $this->createPayment($quote, $this->getTransactionByQuote($quote), [
            "Magento Quote ID" => (string) $quote->getId(),
            "Magento Store ID" => (string) $quote->getStoreId(),
        ]);
    }

    /**
     * @param OrderInterface|CartInterface $payable
     * @param PaymentTransactionDataInterface $transaction
     * @param array $metadata
     * @return PaymentCreatedInterface
     * @throws ApiRequestJsonSerializationException
     * @throws ApiResponseUnsuccessfulException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws SignerException
     */
    private function createPayment(OrderInterface|CartInterface $payable, PaymentTransactionDataInterface $transaction, array $metadata): PaymentCreatedInterface
    {
        $this->logger->addPrefix('PaymentCreationService')->debug('Start');

        // Get the TL user id if we recognise the email address
        $customerEmail = $payable->getBillingAddress()->getEmail() ?: $payable->getCustomerEmail();
        $existingUser = $this->userRepository->get($customerEmail);
        $existingUserId = $existingUser["truelayer_id"] ?? null;

        // Create the TL payment
        $client = $this->clientFactory->create((int) $payable->getStoreId());
        $this->logger->debug('Create client');

        $merchantAccountId = $this->getMerchantAccountId($client, $payable->getBaseCurrencyCode());
        $this->logger->debug('Merchant account', $merchantAccountId);

        $amountInMinor = AmountHelper::toMinor($payable->getBaseGrandTotal());
        $paymentConfig = $this->createPaymentConfig($payable, $amountInMinor, $merchantAccountId, $customerEmail, $metadata, $existingUserId);

        try {
            $payment = $client->payment()->fill($paymentConfig)->create();
        } catch (ApiResponseUnsuccessfulException $e) {
            $this->logger->error('API validation errors', $e->getErrors());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Failed creating payment', $e);
            throw $e;
        }

        $this->logger->debug('Created payment', $payment->getId());

        // If new user, we save it
        if (!$existingUser) {
            $this->userRepository->set($customerEmail, $payment->getUserId());
            $this->logger->debug('Saved new user');
        }

        // Link the quote id to the payment id in the transaction table
        $transaction
            ->setPaymentUuid($payment->getId())
            ->setAmount($amountInMinor)
            ->setAmountRequiresValidation(true);
        $this->transactionRepository->save($transaction);
        $this->logger->debug('Payment transaction created', $transaction->getEntityId());

        return $payment;
    }

    /**
     * @param OrderInterface|CartInterface $order
     * @param int $amountInMinor
     * @param string $merchantAccountId
     * @param string $customerEmail
     * @param array $metadata
     * @param string|null $existingUserId
     * @return array
     */
    private function createPaymentConfig(
        OrderInterface|CartInterface $order,
        int $amountInMinor,
        string $merchantAccountId,
        string $customerEmail,
        array $metadata,
        string $existingUserId = null
    ): array {
        $config = [
            "amount_in_minor" => $amountInMinor,
            "currency" => $order->getBaseCurrencyCode(),
            "payment_method" => [
                "retry" => new stdClass(),
                "provider_selection" => [
                    "filter" => [
                        "release_channel" => $this->configRepository->getReleaseChannel((int) $order->getStoreId()),
                        "customer_segments" => $this->configRepository->getBankingProviders(),
                    ],
                    "type" => "user_selected",
                ],
                "type" => "bank_transfer",
                "beneficiary" => [
                    "type" => "merchant_account",
                    "name" => $this->configRepository->getMerchantAccountName(),
                    "merchant_account_id" => $merchantAccountId
                ]
            ],
            "user" => [
                "id" => $existingUserId,
                "name" => trim($order->getBillingAddress()->getFirstname()) .
                    ' ' .
                    trim($order->getBillingAddress()->getLastname()),
                "email" => $customerEmail
            ],
            "metadata" => $metadata,
        ];

        $this->logger->debug('Payment config', $config);

        return $config;
    }

    /**
     * @param ClientInterface $client
     * @param string $currencyCode
     * @return string
     * @throws ApiRequestJsonSerializationException
     * @throws ApiResponseUnsuccessfulException
     * @throws InvalidArgumentException
     * @throws SignerException
     * @throws Exception
     */
    private function getMerchantAccountId(ClientInterface $client, string $currencyCode): string
    {
        foreach ($client->getMerchantAccounts() as $merchantAccount) {
            if ($merchantAccount->getCurrency() == $currencyCode) {
                return $merchantAccount->getId();
            }
        }

        throw new Exception('No merchant account found');
    }

    /**
     * @param OrderInterface $order
     * @return PaymentTransactionDataInterface
     * @throws LocalizedException
     */
    private function getTransactionByOrder(OrderInterface $order): PaymentTransactionDataInterface
    {
        try {
            return $this->transactionRepository->getByOrderId((int) $order->getEntityId());
        } catch (NoSuchEntityException) {
            $transaction = $this->transactionRepository->create()
                ->setOrderId((int) $order->getEntityId())
                ->setQuoteId((int) $order->getQuoteId())
                ->setToken($this->mathRandom->getUniqueHash('trl'));

            return $this->transactionRepository->save($transaction);
        }
    }

    /**
     * @param CartInterface $quote
     * @return PaymentTransactionDataInterface
     * @throws LocalizedException
     */
    private function getTransactionByQuote(CartInterface $quote): PaymentTransactionDataInterface
    {
        $transaction = $this->transactionRepository->getOneByColumns([
            PaymentTransactionDataInterface::QUOTE_ID => (int) $quote->getId(),
            PaymentTransactionDataInterface::ORDER_ID => ['null' => true],
        ], [PaymentTransactionDataInterface::ENTITY_ID => 'DESC']);

        if ($transaction) {
            return $transaction;
        }

        return $this->transactionRepository->create()
            ->setQuoteId((int) $quote->getId())
            ->setToken($this->mathRandom->getUniqueHash('trl'));
    }
}
