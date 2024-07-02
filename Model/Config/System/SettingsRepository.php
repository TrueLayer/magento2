<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\System;

use TrueLayer\Connect\Api\Config\System\SettingInterface;

/**
 * Debug provider class
 */
class SettingsRepository extends BaseRepository implements SettingInterface
{
    /**
     * @inheritDoc
     */
    public function getMinimumOrderTotal(): float
    {
        return (float)$this->getStoreValue(self::XML_PATH_MINIMUM_ORDER_TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function getMaximumOrderTotal(): float
    {
        return (float)$this->getStoreValue(self::XML_PATH_MAXIMUM_ORDER_TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function getBankingProviders(?int $storeId = null): array
    {
        $providers = $this->getStoreValue(self::XML_PATH_BANKING_PROVIDERS, $storeId);
        if ($providers) {
            return explode(',', $providers);
        } else {
            return ['retail'];
        }
    }

    /**
     * @inheritDoc
     */
    public function getPaymentPagePrimaryColor(): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_PAYMENT_PAGE_PRIMARY_COLOR);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentPageSecondaryColor(): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_PAYMENT_PAGE_SECONDARY_COLOR);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentPageTertiaryColor(): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_PAYMENT_PAGE_TERTIARY_COLOR);
    }

    /**
     * @inheritDoc
     */
    public function sendInvoiceEmail(): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEND_INVOICE_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function sendOrderEmail(): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEND_ORDER_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->getStoreValue(self::XML_PATH_DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function getShowDescription(): bool
    {
        return $this->isSetFlag(self::XML_PATH_SHOW_DESCRIPTION);
    }
}
