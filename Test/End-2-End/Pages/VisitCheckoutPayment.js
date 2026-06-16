import { expect } from "@playwright/test";
import Cart from "../Components/Cart";
import { v7 as uuidv7 } from "uuid";

export default class VisitCheckoutPayment {
  constructor(page) {
    this.page = page;
  }

  async visit() {
    await new Cart(this.page).addStandardItemToCart();

    await this.page.goto("./checkout");

    await this.page
      .getByLabel("Email address", { exact: true })
      .fill(uuidv7() + "@example.com");
    await this.page.getByLabel("First name").fill("John");
    await this.page.getByLabel("Last name").fill("Doe");
    await this.page.getByLabel("Street address").fill("123 Main St");
    await this.page.getByLabel("City").fill("London");
    await this.page.getByLabel("Country").selectOption("United Kingdom");
    await this.loadersShouldBeHidden();
    await this.page.getByLabel("ZIP").fill("SW1A 1AA");
    await this.page.getByLabel("Phone number").fill("+447500000000");

    const proceed = this.page.getByRole("button", {
      name: "Proceed to review & payments",
    });
    const paymentHeading = this.page.getByRole("heading", {
      name: "Payment Method",
    });

    // Selecting the shipping method triggers a Magewire update that enables "Proceed". The radio
    // click can land before Magewire is ready, so retry selecting until the button is enabled.
    await expect(async () => {
      await this.page
        .locator('input[name="shipping-method-option"]')
        .first()
        .click();
      await this.loadersShouldBeHidden();
      await expect(proceed).toBeEnabled({ timeout: 5000 });
    }).toPass({ timeout: 30000 });

    // A Magewire re-render can drop the "Proceed" click and leave us on the shipping step. Retry
    // until the payment step renders, but only click while "Proceed" is still shown so we never
    // click a stale button after the step has already advanced.
    await expect(async () => {
      if (await proceed.isVisible().catch(() => false)) {
        await proceed.click({ timeout: 5000 }).catch(() => {});
      }
      await expect(paymentHeading).toBeVisible({ timeout: 8000 });
    }).toPass({ timeout: 30000 });
  }

  async loadersShouldBeHidden() {
    await expect(this.page.locator("#magewire-loader")).toBeHidden();
  }
}
