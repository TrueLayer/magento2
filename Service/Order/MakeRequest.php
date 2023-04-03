<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteRepository;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\User\RepositoryInterface as UserRepository;
use TrueLayer\Connect\Service\Api\GetClient;
use TrueLayer\Interfaces\Client\ClientInterface;

class MakeRequest
{

    public const REQUEST_EXCEPTION = 'Unable to fetch redirect url';

    /**
     * @var ConfigRepository
     */
    private $configProvider;
    /**
     * @var GetClient
     */
    private $getClient;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var AddressFactory
     */
    private $quoteAddressFactory;
    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var UserRepository
     */
    private $userRepository;

    private $truelayerUser = null;

    /**
     * MakeRequest constructor.
     *
     * @param ConfigRepository $configProvider
     * @param Session $checkoutSession
     * @param GetClient $getClient
     * @param QuoteRepository $quoteRepository
     * @param CartManagementInterface $cartManagement
     * @param TransactionRepository $transactionRepository
     * @param AddressFactory $quoteAddressFactory
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param UserRepository $userRepository
     */
    public function __construct(
        ConfigRepository $configProvider,
        Session $checkoutSession,
        GetClient $getClient,
        QuoteRepository $quoteRepository,
        CartManagementInterface $cartManagement,
        TransactionRepository $transactionRepository,
        AddressFactory $quoteAddressFactory,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        UserRepository $userRepository
    ) {
        $this->configProvider = $configProvider;
        $this->checkoutSession = $checkoutSession;
        $this->getClient = $getClient;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->transactionRepository = $transactionRepository;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->userRepository = $userRepository;
    }

    /**
     * Executes TrueLayer Api for Order Request and returns redirect to platform Url
     *
     * @param string $token
     * @return string
     * @throws AuthenticationException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \TrueLayer\Exceptions\InvalidArgumentException
     * @throws \TrueLayer\Exceptions\ValidationException
     */
    public function execute(string $token): string
    {
        $transaction = $this->transactionRepository->getByToken($token);
        $quote = $this->quoteRepository->get($transaction->getQuoteId());
        $quote->collectTotals();

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
            $this->quoteRepository->save($quote);
        }

        $client = $this->getClient->execute($quote->getStoreId());
        if (!$client) {
            throw new AuthenticationException(
                __('Credentials are not correct')
            );
        }

        $merchantAccountId = $this->getMerchantAccountId($client, $quote);

        $paymentData = $this->prepareData($quote, $merchantAccountId);
        $payment = $client->payment()->fill($paymentData)->create();
        if (!$this->truelayerUser) {
            $this->userRepository->set(
                $quote->getBillingAddress()->getEmail() ?: $quote->getCustomerEmail(),
                $payment->getUserId()
            );
        }

        if ($payment->getId()) {
            $transaction->setUuid($payment->getId());
            $this->transactionRepository->save($transaction);
            $this->duplicateCurrentQuote($quote);
            return $payment->hostedPaymentsPage()
                ->returnUri($this->getReturnUrl())
                ->primaryColour($this->configProvider->getPaymentPagePrimaryColor())
                ->secondaryColour($this->configProvider->getPaymentPageSecondaryColor())
                ->tertiaryColour($this->configProvider->getPaymentPageTertiaryColor())
                ->toUrl();
        }

        $msg = self::REQUEST_EXCEPTION;
        throw new LocalizedException(__($msg));
    }

    /**
     * @param ClientInterface $client
     * @param Quote $quote
     * @return string|null
     * @throws LocalizedException
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\InvalidArgumentException
     * @throws \TrueLayer\Exceptions\SignerException
     * @throws \TrueLayer\Exceptions\ValidationException
     */
    private function getMerchantAccountId(ClientInterface $client, Quote $quote)
    {
        $merchantAccounts = $client->getMerchantAccounts();
        foreach ($merchantAccounts as $merchantAccount) {
            if ($merchantAccount->getCurrency() == $quote->getBaseCurrencyCode()) {
                return $merchantAccount->getId();
            }
        }
        throw new LocalizedException(__('No merchant account found'));
    }

    /**
     * @param Quote $quote
     *
     * @return array
     */
    private function prepareData(Quote $quote, string $merchantAccountId): array
    {
        $customerEmail = $quote->getBillingAddress()->getEmail() ?: $quote->getCustomerEmail();

        $data = [
            "amount_in_minor" => (int)($quote->getBaseGrandTotal() * 100),
            "currency" => $quote->getBaseCurrencyCode(),
            "payment_method" => [
                "provider_selection" => [
                    "filter" => [
                        "countries" => [
                            $quote->getShippingAddress()->getCountryId()
                        ],
                        "release_channel" => "general_availability",
                        "customer_segments" => $this->configProvider->getBankingProviders(),
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
                    "name" => $this->configProvider->getMerchantAccountName(),
                    "merchant_account_id" => $merchantAccountId
                ]
            ],
            "user" => [
                "name" => trim($quote->getBillingAddress()->getFirstname()) .
                    ' ' .
                    trim($quote->getBillingAddress()->getLastname()),
                "email" => $customerEmail,
                "phone" => $quote->getBillingAddress()->getTelephone()
            ]
        ];

        $this->truelayerUser = $this->userRepository->get($customerEmail);
        if ($this->truelayerUser) {
            $data['user']['id'] = $this->truelayerUser['truelayer_id'];
        }

        return $data;
    }

    /**
     * Duplicate current quote and set this as active session.
     * This prevents quotes to change during checkout process
     *
     * @param Quote $quote
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    private function duplicateCurrentQuote(Quote $quote)
    {
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);
        if ($customerId = $quote->getCustomerId()) {
            $cartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
        } else {
            $cartId = $this->cartManagement->createEmptyCart();
        }
        $newQuote = $this->quoteRepository->get($cartId);
        $newQuote->merge($quote);

        $newQuote->removeAllAddresses();
        if (!$quote->getIsVirtual()) {
            $addressData = $this->dataObjectProcessor->buildOutputDataArray(
                $quote->getShippingAddress(),
                AddressInterface::class
            );
            unset($addressData['id']);
            $shippingAddress = $this->quoteAddressFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $shippingAddress,
                $addressData,
                AddressInterface::class
            );
            $newQuote->setShippingAddress(
                $shippingAddress
            );
        }

        $addressData = $this->dataObjectProcessor->buildOutputDataArray(
            $quote->getBillingAddress(),
            AddressInterface::class
        );
        unset($addressData['id']);
        $billingAddress = $this->quoteAddressFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $billingAddress,
            $addressData,
            AddressInterface::class
        );
        $newQuote->setBillingAddress(
            $billingAddress
        );

        $newQuote->setTotalsCollectedFlag(false)->collectTotals();
        $this->quoteRepository->save($newQuote);

        $this->checkoutSession->replaceQuote($newQuote);
    }

    /**
     * Get return url
     *
     * @return string
     */
    private function getReturnUrl(): string
    {
        return $this->configProvider->getBaseUrl() . 'truelayer/checkout/process/';
    }
}
