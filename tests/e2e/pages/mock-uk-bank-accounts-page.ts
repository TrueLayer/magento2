import { Page } from '@playwright/test';

export class MockUkBankAccountsPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    continueButton = () => this.page.getByRole('button', { name: 'Continue' });

    // Methods
    async selectAccountAndContinue() {
        await this.continueButton().isVisible({timeout:10000});
        await this.continueButton().click({force: true});
    }
}