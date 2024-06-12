<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Webapi;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteIdMaskFactory;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Webapi\CheckoutInterface;
use TrueLayer\Connect\Service\Order\MakeRequest as MakeOrderRequest;
use TrueLayer\Connect\Service\Transaction\GenerateToken;

class Checkout implements CheckoutInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var MakeOrderRequest
     */
    private $orderRequest;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;
    /**
     * @var GenerateToken
     */
    private $generateTokenService;

    /**
     * Checkout constructor.
     *
     * @param MakeOrderRequest $orderRequest
     * @param LogRepository $logRepository
     * @param CheckoutSession $checkoutSession
     * @param GenerateToken $generateToken
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        MakeOrderRequest $orderRequest,
        LogRepository $logRepository,
        CheckoutSession $checkoutSession,
        GenerateToken $generateToken,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->orderRequest = $orderRequest;
        $this->logRepository = $logRepository;
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->generateTokenService = $generateToken;
    }

    /**
     * @inheritDoc
     */
    public function orderRequest(bool $isLoggedIn, string $cartId)
    {
        $token = $this->getToken($isLoggedIn, $cartId);
        //web api can't return first level associative array
        $return = [];
        try {
            $response = $this->orderRequest->execute($token);
            $return['response'] = ['success' => true, 'response' => $response];
            return $return;
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Checkout endpoint', $exception->getMessage());
            $return['response'] = ['success' => false, 'message' => $exception->getMessage()];
            return $return;
        }
    }

    /**
     * @param bool $isLoggedIn
     * @param string $cartId
     * @return string|null
     */
    private function getToken(bool $isLoggedIn, string $cartId): ?string
    {
        try {
            if ($isLoggedIn) {
                $quote = $this->checkoutSession->getQuote();
                return $this->generateTokenService->execute((int)$quote->getId());
            } else {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                return $this->generateTokenService->execute((int)$quoteIdMask->getQuoteId());
            }
        } catch (\Exception $e) {
            return '';
        }
    }
}
