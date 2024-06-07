<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\Plugin\AuthenticationException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Math\Random;
use TrueLayer\Connect\Api\Transaction\Data\DataInterface;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\User\RepositoryInterface as UserRepository;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Connect\Service\Log\LogService;
use TrueLayer\Exceptions\ApiRequestJsonSerializationException;
use TrueLayer\Exceptions\ApiResponseUnsuccessfulException;
use TrueLayer\Exceptions\InvalidArgumentException;
use TrueLayer\Exceptions\SignerException;
use TrueLayer\Interfaces\Client\ClientInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Interfaces\Payment\PaymentCreatedInterface;

class PaymentCreationService
{
    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @var ConfigRepository
     */
    private ConfigRepository $configRepository;

    /**
     * @var TransactionRepository
     */
    private TransactionRepository $transactionRepository;

    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * @var Random
     */
    private Random $mathRandom;

    /**
     * @var LogService
     */
    private LogService $logger;

    /**
     * @param ClientFactory $clientFactory
     * @param ConfigRepository $configRepository
     * @param TransactionRepository $transactionRepository
     * @param UserRepository $userRepository
     * @param Random $mathRandom
     * @param LogService $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        ConfigRepository $configRepository,
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        Random $mathRandom,
        LogService $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->configRepository = $configRepository;
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->mathRandom = $mathRandom;
        $this->logger = $logger->prefix('PaymentCreationService');
    }

    /**
     * @param OrderInterface $order
     * @return PaymentCreatedInterface
     * @throws AuthenticationException
     * @throws ApiRequestJsonSerializationException
     * @throws ApiResponseUnsuccessfulException
     * @throws InvalidArgumentException
     * @throws SignerException
     * @throws LocalizedException
     */
    public function createPayment(OrderInterface $order): PaymentCreatedInterface
    {
        $this->logger->prefix("PaymentCreationService {$order->getEntityId()}");
        $this->logger->debug('Start');

        // Get the TL user id if we recognise the email address
        $customerEmail = $order->getBillingAddress()->getEmail() ?: $order->getCustomerEmail();
        $existingUser = $this->userRepository->get($customerEmail);
        $existingUserId = $existingUser["truelayer_id"] ?? null;

        // Create the TL payment
        $this->logger->debug('Create client');
        $client = $this->clientFactory->create((int) $order->getStoreId());

        $this->logger->debug('Find merchant account id');
        $merchantAccountId = $this->getMerchantAccountId($client, $order);

        $this->logger->debug('Create payment');
        $paymentConfig = $this->createPaymentConfig($order, $merchantAccountId, $customerEmail, $existingUserId);
        $payment = $client->payment()->fill($paymentConfig)->create();

        // If new user, we save it
        if (!$existingUser) {
            $this->logger->debug('Save new user');
            $this->userRepository->set($customerEmail, $payment->getUserId());
        }

        // Link the quote id to the payment id in the transaction table
        $this->logger->debug('Create transaction entry');
        $transaction = $this->getTransaction($order)->setPaymentUuid($payment->getId());
        $this->transactionRepository->save($transaction);

        return $payment;
    }

    /**
     * @param OrderInterface $order
     * @param string $merchantAccId
     * @param string $customerEmail
     * @param string|null $existingUserId
     * @return array
     */
    private function createPaymentConfig(OrderInterface $order, string $merchantAccId, string $customerEmail, string $existingUserId = null): array
    {
        $config = [
            "amount_in_minor" => (int) round($order->getBaseGrandTotal() * 100, 0, PHP_ROUND_HALF_UP),
            "currency" => $order->getBaseCurrencyCode(),
            "payment_method" => [
                "provider_selection" => [
                    "filter" => [
                        "countries" => [
                            $order->getShippingAddress()->getCountryId()
                        ],
                        "release_channel" => "general_availability",
                        "customer_segments" => $this->configRepository->getBankingProviders(),
                        "excludes" => [
                            "provider_ids" => [
                                "ob-exclude-this-bank"
                            ]
                        ]
                    ],
                    "type" => "user_selected"
                ],
                "type" => "bank_transfer",
                "beneficiary" => [
                    "type" => "merchant_account",
                    "name" => $this->configRepository->getMerchantAccountName(),
                    "merchant_account_id" => $merchantAccId
                ]
            ],
            "user" => [
                "id" => $existingUserId,
                "name" => trim($order->getBillingAddress()->getFirstname()) .
                    ' ' .
                    trim($order->getBillingAddress()->getLastname()),
                "email" => $customerEmail
            ],
            "metadata" => [
                "Magento Order ID" => $order->getEntityId(),
                "Magento Store ID" => $order->getStoreId(),
            ]
        ];

        $this->logger->debug('Payment config', $config);

        return $config;
    }

    /**
     * @param ClientInterface $client
     * @param OrderInterface $order
     * @return string
     * @throws ApiRequestJsonSerializationException
     * @throws ApiResponseUnsuccessfulException
     * @throws InvalidArgumentException
     * @throws SignerException
     * @throws Exception
     */
    private function getMerchantAccountId(ClientInterface $client, OrderInterface $order): string
    {
        foreach ($client->getMerchantAccounts() as $merchantAccount) {
            if ($merchantAccount->getCurrency() == $order->getBaseCurrencyCode()) {
                return $merchantAccount->getId();
            }
        }

        throw new Exception(__('No merchant account found'));
    }

    /**
     * @param OrderInterface $order
     * @return DataInterface
     * @throws LocalizedException
     */
    private function getTransaction(OrderInterface $order): DataInterface
    {
        try {
            return $this->transactionRepository->getByOrderId((int) $order->getEntityId());
        } catch (Exception $exception) {
            $transaction = $this->transactionRepository->create()
                ->setOrderId((int) $order->getEntityId())
                ->setQuoteId((int) $order->getQuoteId())
                ->setToken($this->mathRandom->getUniqueHash('trl'));

            return $this->transactionRepository->save($transaction);
        }
    }
}
