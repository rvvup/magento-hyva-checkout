import { expect } from "@playwright/test";

export default class Cart {
  constructor(page) {
    this.page = page;
  }

  async addStandardItemToCart() {
    await this.page.goto("./affirm-water-bottle.html");
    await this.page
      .getByRole("button", { name: "Add to Cart" })
      .first()
      .click();
    await expect(
      this.page.getByText(/You added [A-Za-z0-9 ]+ to your shopping cart/i),
    ).toBeVisible();
  }
}
