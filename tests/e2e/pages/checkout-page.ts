import { expect, Page } from '@playwright/test';

export class CheckoutPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    emailField = () => this.page.locator('#customer-email');
    firstNameField = () => this.page.getByRole('textbox', { name: 'First name' });
    lastNameField = () => this.page.getByRole('textbox', { name: 'Last name' });
    addressField = () => this.page.getByRole('textbox', { name: 'Street Address' }).first();
    countryField = () => this.page.locator('select[name="country_id"]'); //.getByText('United Kingdom'); //, {name: 'Country'});
    stateField = () => this.page.getByRole('textbox', { name: 'State/Province' });
    cityField = () => this.page.getByRole('textbox', { name: 'City' });
    postcodeField = () => this.page.getByRole('textbox', { name: 'Zip/Postal Code' });
    phoneNumberField = () => this.page.getByRole('textbox', { name: 'Phone Number' });
    nextStepButton = () => this.page.getByRole('button', { name: 'Next' });
    paymentMethodSelector = 'input[type="radio"]#truelayer';
    paymentMethodButton = () => this.page.locator(this.paymentMethodSelector);
    placeOrderButton = () => this.page.getByRole('button', { name: 'Place Order' });

    private widgetInitPaymentframe = () => this.page.locator('#tl-checkout-widget').contentFrame();
    private widgetBankingModal = () => this.page.locator('#tl-hpp-next').contentFrame();
    widgetPayButton = () => this.widgetInitPaymentframe().getByTestId('checkout-button-loaded');
    widgetSelectBankModal = () => this.widgetInitPaymentframe().getByText('Select your bank');
    widgetMockBank = () => this.widgetBankingModal().getByTestId('provider-list-item-mock-payments-gb-redirect');
    widgetContinueButton = () => this.widgetBankingModal().getByTestId('go-to-bank-button');
    widgetContinueOnDesktopButton = () => this.widgetBankingModal().getByText('on this device');
    widgetPaymentBeingProcessedText = () => this.widgetBankingModal().getByText('confirming your payment', {exact: false})

    // Methods

    async fillShippingDetailsAndSubmit(email: string, isMobile: boolean = false) {
        await this.emailField().isVisible();
        await this.emailField().fill(email);
        await this.firstNameField().isVisible();
        await this.firstNameField().fill('Automated');
        await this.lastNameField().isVisible();
        await this.lastNameField().fill('Test');
        await this.countryField().isVisible();
        await this.countryField().selectOption('United Kingdom')
        await this.phoneNumberField().isVisible();
        await this.phoneNumberField().fill('1234567890');
        await this.cityField().isVisible();
        await this.cityField().fill('London');
        await this.addressField().isVisible();
        await this.addressField().fill('10 Downing Street')
        await this.postcodeField().isVisible();
        await this.postcodeField().fill('SW1A 2AB');
        if (isMobile) {
            await this.page.locator('body').click({position: {x: 0, y: 0}});
        }

        await this.submitShippingInfoAndWaitForPageLoad();
    }

    async navigateToShippingStep() {
        const url = `${process.env.E2E_TEST_URL as string}/checkout/#shipping`;
        await this.page.goto(url);
        await this.page.waitForSelector('.loader', { state: 'detached' });
    }

    async submitShippingInfoAndWaitForPageLoad() {
        await expect(this.nextStepButton()).toBeEnabled({timeout: 5000});
        await this.nextStepButton().click();
        await this.page.waitForSelector(this.paymentMethodSelector, { state: 'visible', timeout: 15000 });
    }

    async clickPaymentMethod() {
        this.paymentMethodButton().isVisible();
        this.paymentMethodButton().isEnabled();
        this.paymentMethodButton().click();
        await this.page.waitForSelector('.loading-mask', { state: 'visible' });
        await this.page.waitForSelector('.loading-mask', { state: 'hidden' });
    }

    async clickPlaceOrderButton() {
        await this.placeOrderButton().isVisible();
        await this.placeOrderButton().isEnabled();
        await this.placeOrderButton().click();
    }

    async clickWidgetPayButton() {
        await expect(this.widgetPayButton()).toBeVisible({timeout: 5000});
        await this.widgetPayButton().click();
    }

    async selectWidgetMockBankAndContinueOnDesktop() {
        await this.selectWidgetMockBankAndContinue();
        await this.widgetContinueOnDesktopButton().click();
    }

    async selectMockBankAndContinueOnMobile() {
        await this.selectWidgetMockBankAndContinue();
    }

    private async selectWidgetMockBankAndContinue(){
        await expect(this.widgetMockBank()).toBeVisible({timeout: 15000})
        await this.widgetMockBank().click();
        await expect(this.widgetContinueButton()).toBeVisible({timeout: 10000})
        await this.widgetContinueButton().click();
    }

    async expectPaymentProcessingText() {
        await expect(this.widgetPaymentBeingProcessedText()).toBeVisible({timeout: 5000});
    }
}
