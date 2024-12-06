import { expect, Page } from '@playwright/test';

export class ProductPage {
    page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    // Locators
    addToCartButton = () => this.page.getByRole('button', { name: 'Add to Cart' });
    cartCounter = () => this.page.locator('.counter-number');


    // Methods
    async addToCart() {
        await this.addToCartButton().click();
        const cartCounter = this.cartCounter()
        await cartCounter.isVisible();
        await expect(cartCounter).toHaveText('1', {timeout: 5000})
    }

    async navigateTo(){
        const url = `${process.env.E2E_TEST_URL as string}/catalog/product/view/id/1/s/test-product/category/2/`;
        await this.page.goto(url);
    }
}
