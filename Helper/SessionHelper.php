<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session;

class SessionHelper
{
    private CartRepositoryInterface $quoteRepository;
    private Session $session;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session $session
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->session = $session;
    }

    /**
     * @param int $quoteId
     */
    public function allowQuoteRestoration(int $quoteId): void
    {
        $allowed = $this->getAllowedQuotes();
        $allowed[] = $quoteId;
        $this->session->setAllowedQuotes($allowed);
    }

    /**
     * @param int $quoteId
     */
    public function restoreQuote(int $quoteId): void
    {
        if (!in_array($quoteId, $this->getAllowedQuotes())) {
            return;
        }

        try {
            $quote = $this->quoteRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            return;
        }

        if (!$quote->getId()) {
            return;
        }

        $quote->setIsActive(1)->setReservedOrderId(NULL);
        $this->quoteRepository->save($quote);

        $this->session->replaceQuote($quote);
        $this->session->unsLastRealOrderId();
    }

    /**
     * @return array
     */
    private function getAllowedQuotes(): array
    {
        return $this->session->getAllowedQuotes() ?? [];
    }
}

