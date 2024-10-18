import { expect, Page } from '@playwright/test';

export class PaymentConfirmationPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    orderBeingProcessedText = () => this.page.getByText('Confirming your payment')
    paymentConfirmedText = () => this.page.getByRole('heading', { name: 'All done' });
    continueButton = () => this.page.getByRole('button', {name: 'continue'})

    // Methods
    async waitForProcessingAndContinue() {
        // await expect(this.orderBeingProcessedText()).toBeVisible({timeout: 10000});
        await expect(this.continueButton()).toBeVisible({timeout: 10000});
        await this.continueButton().click();
    }
}