<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\System;

use TrueLayer\Connect\Api\Config\System\SettingsRepositoryInterface;
use TrueLayer\Connect\Model\Config\Source\ReleaseChannel;

/**
 * Debug provider class
 */
class SettingsRepository extends BaseRepository implements SettingsRepositoryInterface
{
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
    public function getReleaseChannel(?int $storeId = null): string
    {
        return $this->getStoreValue(self::XML_PATH_RELEASE_CHANNEL, $storeId) ?: ReleaseChannel::GENERAL_AVAILABILITY;
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
    public function isPreselected(): bool
    {
        return $this->isSetFlag(self::XML_PATH_PRESELECTED);
    }

    /**
     * @inheritDoc
     */
    public function isCheckoutWidgetEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_CHECKOUT_WIDGET_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function isCheckoutWidgetRecommended(): bool
    {
        return $this->isCheckoutWidgetEnabled() && $this->isSetFlag(self::XML_PATH_CHECKOUT_WIDGET_RECOMMENDED);
    }
}
