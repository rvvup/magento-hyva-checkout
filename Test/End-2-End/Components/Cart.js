import {expect} from "@playwright/test";

export default class Cart {
    constructor(page) {
        this.page = page;
    }

    async addItemToCart(itemName) {
        await this.page.goto('./');
        await this.page.locator('button[aria-label="Add to Cart '+itemName+'"]').click();
        await expect(this.page.getByRole('button', { name: 'Toggle minicart, You have 1 product in your cart.'})).toBeVisible();
    }
}
