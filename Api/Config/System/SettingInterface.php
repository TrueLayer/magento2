<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Config\System;

/**
 * Settings group config repository interface
 * @api
 */
interface SettingInterface
{

    /** Settings Group */
    public const XML_PATH_MINIMUM_ORDER_TOTAL = 'payment/truelayer/minimum_order_total';
    public const XML_PATH_MAXIMUM_ORDER_TOTAL = 'payment/truelayer/maximum_order_total';
    public const XML_PATH_SEND_ORDER_EMAIL = 'payment/truelayer/send_order_email';
    public const XML_PATH_SEND_INVOICE_EMAIL = 'payment/truelayer/send_invoice_email';
    public const XML_PATH_BANKING_PROVIDERS = 'payment/truelayer/banking_providers';
    public const XML_PATH_PAYMENT_PAGE_PRIMARY_COLOR = 'payment/truelayer/payment_page_primary_color';
    public const XML_PATH_PAYMENT_PAGE_SECONDARY_COLOR = 'payment/truelayer/payment_page_secondary_color';
    public const XML_PATH_PAYMENT_PAGE_TERTIARY_COLOR = 'payment/truelayer/payment_page_tertiary_color';

    /**
     * Get minimum allowed order total
     *
     * @return float
     */
    public function getMinimumOrderTotal(): float;

    /**
     * Get maximum allowed order total
     *
     * @return float
     */
    public function getMaximumOrderTotal(): float;

    /**
     * Get banking providers
     *
     * @param int|null $storeId
     * @return array
     */
    public function getBankingProviders(?int $storeId = null): array;

    /**
     * Get payment page primary color
     *
     * @return string
     */
    public function getPaymentPagePrimaryColor(): string;

    /**
     * Get payment page secondary color
     *
     * @return string
     */
    public function getPaymentPageSecondaryColor(): string;

    /**
     * Get payment page tertiary color
     *
     * @return string
     */
    public function getPaymentPageTertiaryColor(): string;

    /**
     * Send invoice email flag
     *
     * @return bool
     */
    public function sendInvoiceEmail(): bool;

    /**
     * Send order email flag
     *
     * @return bool
     */
    public function sendOrderEmail(): bool;
}
