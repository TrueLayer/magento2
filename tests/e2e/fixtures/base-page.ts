import { test as base } from "@playwright/test";

import { ProductPage } from "../pages/product-page";
import { CheckoutPage } from "../pages/checkout-page";
import { HostedPaymentsPage } from "../pages/hosted-payments-page";
import { MockUkBankPage } from "../pages/mock-uk-bank-page";
import { PaymentConfirmationPage } from "../pages/payment-confirmation-page";
import { MockUkBankAccountsPage } from "../pages/mock-uk-bank-accounts-page";
import { OrderConfirmationPage } from "../pages/order-confirmation-page";

export const test = base.extend<{
    productPage: ProductPage;
    checkoutPage: CheckoutPage;
    hostedPaymentsPage: HostedPaymentsPage;
    mockUkBankPage: MockUkBankPage;
    mockUkBankAccountsPage: MockUkBankAccountsPage;
    paymentConfirmationPage: PaymentConfirmationPage;
    orderConfirmationPage: OrderConfirmationPage;

}>({
    productPage: async ({ page }, use) => {
        await use(new ProductPage(page));
    },
    checkoutPage: async ({ page }, use) => {
        await use(new CheckoutPage(page));
    },
    hostedPaymentsPage: async ({ page }, use) => {
        await use(new HostedPaymentsPage(page));
    },
    mockUkBankPage: async ({ page }, use) => {
        await use(new MockUkBankPage(page));
    },
    mockUkBankAccountsPage: async ({ page }, use) => {
        await use(new MockUkBankAccountsPage(page));
    },
    paymentConfirmationPage: async ({ page }, use) => {
        await use(new PaymentConfirmationPage(page));
    },
    orderConfirmationPage: async ({ page }, use) => {
        await use(new OrderConfirmationPage(page));
    },
});