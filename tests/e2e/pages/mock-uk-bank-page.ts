import { Page } from '@playwright/test';

export class MockUkBankPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    usernameField = () => this.page.getByPlaceholder('Enter username');
    thirdBox = () => this.page.getByPlaceholder('3rd');
    fourthBox = () => this.page.getByPlaceholder('4th');
    sixthBox = () => this.page.getByPlaceholder('6th');
    continueButton = () => this.page.getByRole('button', { name: 'Continue' });

    // Methods
    async enterOnlineBankingDetailsAndContinue() {
        await this.usernameField().isVisible();
        await this.usernameField().fill('test_executed');
        await this.thirdBox().fill('0');
        await this.fourthBox().fill('0');
        await this.sixthBox().fill('0');
        await this.continueButton().click();
    }
}