import { expect, Page } from '@playwright/test';

export class OrderConfirmationPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    paymentBeingProcessedText = () => this.page.getByText('Please wait, we are processing your payment')
    orderConfirmedText = () => this.page.getByRole('heading', { name: 'Thank you for your purchase!' });

    // Methods
    async waitForProcessingAndReturnToStore() {
        await this.paymentBeingProcessedText().isVisible();
        await expect(this.orderConfirmedText()).toBeVisible({timeout: 1});
    }
}