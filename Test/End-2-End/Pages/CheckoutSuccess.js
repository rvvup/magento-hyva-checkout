import { expect } from "@playwright/test";

export default class CheckoutSuccess {
  constructor(page) {
    this.page = page;
  }

  async waitForSuccess() {
    await this.page.waitForURL("**/checkout/onepage/success/");

    await expect(
      this.page.getByRole("heading", { name: "Thank you for your purchase!" }),
    ).toBeVisible();
  }

  async getOrderNumber() {
    const orderLine = await this.page
      .locator(".checkout-success p")
      .first()
      .innerText();

    const match = orderLine.match(/\d+/);

    expect(match, "Could not find the order number on the success page").not.toBeNull();

    return match[0];
  }
}
