import { expect } from "@playwright/test";

/**
 * The inline card fields rendered by the Rvvup JS SDK (Basis Theory elements). Each field lives in
 * its own cross origin iframe whose id is a random uuid, so everything is addressed through the
 * stable wrapper the SDK mounts them into.
 */
export default class RvvupCardForm {
  static APPROVED_CARD = "4111111111111111";

  static EXPIRY = "12/33";

  static CVV = "123";

  constructor(page) {
    this.page = page;
  }

  container() {
    return this.page.locator("#rvvup-card-form-container");
  }

  numberField() {
    return this.page.frameLocator("#rvvup-card-number iframe").locator("input");
  }

  expiryField() {
    return this.page
      .frameLocator("#rvvup-card-expiration-date iframe")
      .locator("input");
  }

  cvvField() {
    return this.page.frameLocator("#rvvup-card-cvv iframe").locator("input");
  }

  async shouldBeMounted() {
    await expect(this.container()).toBeVisible();
    await expect(this.numberField()).toBeVisible();
    await expect(this.expiryField()).toBeVisible();
    await expect(this.cvvField()).toBeVisible();
  }

  async fill(cardNumber = RvvupCardForm.APPROVED_CARD) {
    await this.shouldBeMounted();

    await this.numberField().fill(cardNumber);
    await this.expiryField().fill(RvvupCardForm.EXPIRY);
    await this.cvvField().fill(RvvupCardForm.CVV);
  }

  async placeOrder() {
    await this.page.getByRole("button", { name: "Place Order" }).click();
  }
}
