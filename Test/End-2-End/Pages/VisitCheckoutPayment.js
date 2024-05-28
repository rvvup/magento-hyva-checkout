import { expect } from "@playwright/test";
import Cart from "../Components/Cart";

export default class VisitCheckoutPayment {
  constructor(page) {
    this.page = page;
  }

  async visit() {
    await new Cart(this.page).addStandardItemToCart();

    await this.page.goto("./checkout");

    await this.page
      .getByLabel("Email address", { exact: true })
      .fill("johndoe@example.com");
    await this.page.getByLabel("First name").fill("John");
    await this.page.getByLabel("Last name").fill("Doe");
    await this.page.getByLabel("Street address").fill("123 Main St");
    await this.page.getByLabel("City").fill("London");
    await this.page.getByLabel("Country").selectOption("United Kingdom");
    await this.page.getByLabel("ZIP").fill("SW1A 1AA");
    await this.page.getByLabel("Phone number").fill("+447500000000");

    await this.page.getByLabel("Fixed").click();

    await this.page.waitForFunction(() =>
      Array.from(
        document.querySelectorAll(".magewire\\.notification\\.message"),
      ).every((element) => element.offsetParent === null),
    );

    await this.page
      .getByRole("button", { name: "Proceed to review & payments" })
      .click();

    await expect(
      this.page.getByRole("heading", { name: "Payment Method" }),
    ).toBeVisible();
  }

  async loadersShouldBeHidden() {
    await this.page.waitForFunction(() =>
      Array.from(
        document.querySelectorAll(".magewire\\.notification\\.message"),
      ).every((element) => element.offsetParent === null),
    );

    await expect(this.page.locator("#magewire-loader")).toBeHidden();
  }
}
