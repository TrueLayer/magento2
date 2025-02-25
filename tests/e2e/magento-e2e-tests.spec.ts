import { chromium, webkit } from "@playwright/test";
import { test } from "./fixtures/base-page";

test.describe('Truelayer magento plugin E2E Tests', () => {
    test.skip('Successful Purchase of a Product using a valid email address', async ({
        isMobile,
        productPage,
        checkoutPage,
        hostedPaymentsPage,
        mockUkBankPage,
        mockUkBankAccountsPage,
        paymentConfirmationPage,
        orderConfirmationPage,
    }) => {
        // arrange
        await productPage.navigateTo();
        await productPage.addToCart();
        await checkoutPage.navigateToShippingStep();
        await checkoutPage.fillShippingDetailsAndSubmit('truelayer@example.com');
        await checkoutPage.clickPaymentMethod();
        await checkoutPage.clickPlaceOrderButton();

        if (isMobile === true) {
            await hostedPaymentsPage.selectMockBankAndContinueOnMobile();
        }
        else {
            await hostedPaymentsPage.selectMockBankAndContinueOnDesktop();
        }
        await mockUkBankPage.enterOnlineBankingDetailsAndContinue();
        await mockUkBankAccountsPage.selectAccountAndContinue();
        // await paymentConfirmationPage.waitForProcessingAndContinue();
        await orderConfirmationPage.waitForProcessingAndReturnToStore();
    })
});
