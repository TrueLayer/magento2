<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Webapi;

use Magento\Framework\Math\Random;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\Data\DataInterface;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\Webapi\CheckoutInterface;
use TrueLayer\Connect\Api\User\RepositoryInterface as UserRepository;
use TrueLayer\Interfaces\Client\ClientInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Service\Api\ClientFactory;
use TrueLayer\Interfaces\Payment\PaymentCreatedInterface;

class Checkout implements CheckoutInterface
{
    private Order $order;
    private Random $mathRandom;
    private LogRepository $logRepository;
    private ConfigRepository $configRepository;
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private ClientFactory $clientFactory;

    /**
     * @param Random $mathRandom
     * @param Session $session
     * @param LogRepository $logRepository
     * @param ConfigRepository $configRepository
     * @param TransactionRepository $transactionRepository
     * @param UserRepository $userRepository
     * @param ClientFactory $clientFactory
     */
    public function __construct(
        Random $mathRandom,
        Session $session,
        LogRepository $logRepository,
        ConfigRepository $configRepository,
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        ClientFactory $clientFactory
    ) {
        $this->order = $session->getLastRealOrder();
        $this->mathRandom = $mathRandom;
        $this->logRepository = $logRepository;
        $this->configRepository = $configRepository;
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @inheritDoc
     */
    public function orderRequest()
    {
        try {
            return [
                'response' => [
                    'success' => true,
                    'payment_page_url' => $this->getHppUrl()
                ]
            ];
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Checkout endpoint', $exception->getMessage());
            
            return [
                'response' => [
                    'success' => false,
                    'message' => $exception->getMessage()
                ]
            ];
        }
    }

    /**
     * @return string
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\InvalidArgumentException
     * @throws \TrueLayer\Exceptions\SignerException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getHppUrl(): string
    {
        // Create the payment with a TL user id if we recognise the email address
        $customerEmail = $this->order->getBillingAddress()->getEmail() ?: $this->order->getCustomerEmail();

        $existingUser = $this->userRepository->get($customerEmail);
        $payment = $this->createPayment($customerEmail, $existingUser["truelayer_id"] ?? null);

        // If new user, we save it
        if (!$existingUser) {
            $this->userRepository->set($customerEmail, $payment->getUserId());
        }

        // Link the quote id to the payment id in the transaction table
        $transaction = $this->getTransaction()->setPaymentUuid($payment->getId());
        $this->transactionRepository->save($transaction);

        return $payment->hostedPaymentsPage()
            ->returnUri($this->configRepository->getBaseUrl() . 'truelayer/checkout/process/')
            ->primaryColour($this->configRepository->getPaymentPagePrimaryColor())
            ->secondaryColour($this->configRepository->getPaymentPageSecondaryColor())
            ->tertiaryColour($this->configRepository->getPaymentPageTertiaryColor())
            ->toUrl();
    }

    /**
     * @param string $customerEmail
     * @param string|null $existingUserId
     * @return PaymentCreatedInterface
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\InvalidArgumentException
     * @throws \TrueLayer\Exceptions\SignerException
     */
    private function createPayment(string $customerEmail, string $existingUserId = null): PaymentCreatedInterface
    {
        // Get a client configured for the store
        $client = $this->clientFactory->create((int) $this->order->getStoreId());

        if (!$client) {
            throw new AuthenticationException(__('Credentials are not correct'));
        }

        $merchantAccountId = $this->getMerchantAccountId($client);

        $paymentConfig = [
            "amount_in_minor" => (int) round($this->order->getBaseGrandTotal() * 100, 0, PHP_ROUND_HALF_UP),
            "currency" => $this->order->getBaseCurrencyCode(),
            "payment_method" => [
                "provider_selection" => [
                    "filter" => [
                        "countries" => [
                            $this->order->getShippingAddress()->getCountryId()
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
                    "merchant_account_id" => $merchantAccountId
                ]
            ],
            "user" => [
                "id" => $existingUserId,
                "name" => trim($this->order->getBillingAddress()->getFirstname()) .
                    ' ' .
                    trim($this->order->getBillingAddress()->getLastname()),
                "email" => $customerEmail
            ]
        ];

        $this->logRepository->addDebugLog('payment creation request', $paymentConfig);

        return $client->payment()->fill($paymentConfig)->create();
    }

    /**
     * @param ClientInterface $client
     * @return string
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\InvalidArgumentException
     * @throws \TrueLayer\Exceptions\SignerException
     */
    private function getMerchantAccountId(ClientInterface $client): string
    {
        foreach ($client->getMerchantAccounts() as $merchantAccount) {
            if ($merchantAccount->getCurrency() == $this->order->getBaseCurrencyCode()) {
                return $merchantAccount->getId();
            }
        }

        throw new \Exception(__('No merchant account found'));
    }

    /**
     * @return DataInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getTransaction(): DataInterface
    {
        try {
            return $this->transactionRepository->getByQuoteId((int) $this->order->getQuoteId(), true);
        } catch (\Exception $exception) {
            $transaction = $this->transactionRepository->create()
                ->setQuoteId((int) $this->order->getQuoteId())
                ->setToken($this->mathRandom->getUniqueHash('trl'));

            return $this->transactionRepository->save($transaction);
        }
    }
}
