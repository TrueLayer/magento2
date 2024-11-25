import { expect, Page } from '@playwright/test';

export class HostedPaymentsPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    mockBank = () => this.page.getByLabel('Mock UK Payments - Redirect Flow', { exact: true });
    continueButton = () => this.page.getByTestId('go-to-bank-button');
    continueOnDesktopButton = () => this.page.getByText('on this device');

    // Methods
    async selectMockBankAndContinueOnDesktop() {
        await this.selectMockBankAndContinue();
        await expect(this.continueOnDesktopButton()).toBeVisible({timeout: 10000})
        await this.continueOnDesktopButton().click();
    }

    async selectMockBankAndContinueOnMobile() {
        await this.selectMockBankAndContinue();
    }

    private async selectMockBankAndContinue(){
        await expect(this.mockBank()).toBeVisible({timeout: 10000})
        await this.mockBank().click();
        await expect(this.continueButton()).toBeVisible({timeout: 10000})
        await this.continueButton().click();
    }
}
