import { Page } from '@playwright/test';

export class HostedPaymentsPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    mockBank = () => this.page.getByLabel('Select Mock UK Payments - Redirect Flow', { exact: true });
    continueButton = () => this.page.getByTestId('confirm-redirect-button');
    continueOnDesktopButton = () => this.page.getByTestId('continue-desktop');

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