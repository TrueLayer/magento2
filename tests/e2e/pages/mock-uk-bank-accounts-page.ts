import { expect, Page } from '@playwright/test';

export class MockUkBankAccountsPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    currentAccount = () => this.page.getByText('Select account');
    continueButton = () => this.page.getByRole('button', { name: 'Continue' });

    // Methods
    async selectAccountAndContinue() {
        await expect(this.currentAccount()).toBeVisible({ timeout: 10000 })
        await this.continueButton().isVisible();
        await this.continueButton().click({force: true});
    }
}