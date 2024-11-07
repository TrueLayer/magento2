import { expect, Page } from '@playwright/test';

export class PaymentConfirmationPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    paymentProcessingText = () => this.page.getByText('In progress');
    paymentConfirmedText = () => this.page.getByText('All done');
    continueButton = () => this.page.getByRole('button', {name: 'continue'})

    // Methods
    async waitForProcessingAndContinue() {
        await expect(this.paymentProcessingText().or(this.paymentConfirmedText())).toBeVisible({timeout: 40000})
        await expect(this.continueButton()).toBeVisible();
        await this.continueButton().click();
    }
}