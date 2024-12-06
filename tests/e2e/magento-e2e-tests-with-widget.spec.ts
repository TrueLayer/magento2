import { chromium, webkit } from "@playwright/test";
import { test } from "./fixtures/base-page";
import { MockUkBankAccountsPage } from "./pages/mock-uk-bank-accounts-page";
import { MockUkBankPage } from "./pages/mock-uk-bank-page";

test.describe('Truelayer magento plugin E2E Tests', () => {
    test('Successful Purchase of a Product using a valid email address', async ({
        isMobile,
        productPage,
        checkoutPage,
        orderConfirmationPage,
    }) => {
        // arrange
        await productPage.navigateTo();
        await productPage.addToCart();
        await checkoutPage.navigateToShippingStep();
        await checkoutPage.fillShippingDetailsAndSubmit('truelayer@example.com', isMobile);
        await checkoutPage.clickPaymentMethod();
        await checkoutPage.clickWidgetPayButton();

        const newTab = checkoutPage.page.context().waitForEvent('page');

        if (isMobile === true) {
            await checkoutPage.selectMockBankAndContinueOnMobile();
        }
        else {
            await checkoutPage.selectWidgetMockBankAndContinueOnDesktop();
        }
        const mockUkBankPage = new MockUkBankPage(await newTab);
        const mockUkBankAccountsPage = new MockUkBankAccountsPage(await newTab);
        // }
        await mockUkBankPage.enterOnlineBankingDetailsAndContinue();
        await mockUkBankAccountsPage.selectAccountAndContinue();
        await checkoutPage.expectPaymentProcessingText();
        await orderConfirmationPage.expectOrderConfirmed(30000);
    })
});
