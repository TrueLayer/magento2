import { Page } from '@playwright/test';

export class HostedPaymentsPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    mockBank = () => this.page.getByText('Mock UK Payments - Redirect Flow', { exact: true });
    continueButton = () => this.page.getByTestId('go-to-bank-button');
    continueOnDesktopButton = () => this.page.getByText('on this device');

    // Methods
    async selectMockBankAndContinueOnDesktop() {
        await this.selectMockBankAndContinue();
        await this.continueOnDesktopButton().isVisible()
        await this.continueOnDesktopButton().click();
    }

    async selectMockBankAndContinueOnMobile() {
        await this.selectMockBankAndContinue();
    }

    private async selectMockBankAndContinue(){
        await this.mockBank().isVisible();
        await this.mockBank().click();
        await this.continueButton().isVisible();
        await this.continueButton().click();
    }
}
