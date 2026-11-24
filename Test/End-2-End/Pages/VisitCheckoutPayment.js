import { expect } from "@playwright/test";
import Cart from "../Components/Cart";
import clickCheckoutControl from "../Components/CheckoutControl";
import { v7 as uuidv7 } from "uuid";

export default class VisitCheckoutPayment {
  constructor(page) {
    this.page = page;
    // Set while filling in the shipping details, so a test can look the resulting order up.
    this.guestEmail = null;
  }

  async visit() {
    await new Cart(this.page).addStandardItemToCart();

    await this.page.goto("./checkout");

    await this.fillShippingDetails();
    await this.selectShippingMethod();
    await this.goToPaymentStep();
  }

  async fillShippingDetails() {
    this.guestEmail = uuidv7() + "@example.com";

    await this.page
      .getByLabel("Email address", { exact: true })
      .fill(this.guestEmail);
    await this.page.getByLabel("First name").first().fill("John");
    await this.page.getByLabel("Last name").first().fill("Doe");
    await this.page.getByLabel("Street address").first().fill("123 Main St");
    await this.page.getByLabel("City").first().fill("London");
    await this.page
      .getByLabel("Country")
      .first()
      .selectOption("United Kingdom");
    await this.loadersShouldBeHidden();
    await this.page.getByLabel("ZIP").first().fill("SW1A 1AA");
    await this.page.getByLabel("Phone number").first().fill("+447500000000");
  }

  async selectShippingMethod() {
    const shippingMethod = this.page
      .locator('input[name="shipping-method-option"]')
      .first();
    const proceed = this.page.getByRole("button", {
      name: "Proceed to review & payments",
    });

    // Selecting the shipping method triggers a Magewire update that enables "Proceed" on the default
    // checkout. The radio click can land before Magewire is ready and be reverted by the re-render,
    // so retry selecting until the button it gates is actually usable. The onepage checkout has no
    // such button, where a checked radio is all there is to wait for.
    await expect(async () => {
      await clickCheckoutControl(shippingMethod);
      await this.loadersShouldBeHidden();
      await expect(shippingMethod).toBeChecked({ timeout: 5000 });

      if (await proceed.isVisible().catch(() => false)) {
        await expect(proceed).toBeEnabled({ timeout: 5000 });
      }
    }).toPass({ timeout: 30000 });
  }

  /**
   * The default checkout splits shipping and payment over two steps, the onepage checkout renders
   * both at once and has no button to advance.
   */
  async goToPaymentStep() {
    const proceed = this.page.getByRole("button", {
      name: "Proceed to review & payments",
    });
    const paymentHeading = this.page.getByRole("heading", {
      name: "Payment Method",
    });

    if (await paymentHeading.isVisible().catch(() => false)) {
      return;
    }

    // A Magewire re-render can drop the "Proceed" click and leave us on the shipping step. Retry
    // until the payment step renders, but only click while "Proceed" is still shown so we never
    // click a stale button after the step has already advanced.
    await expect(async () => {
      if (await proceed.isVisible().catch(() => false)) {
        await expect(proceed).toBeEnabled({ timeout: 5000 });
        await clickCheckoutControl(proceed).catch(() => {});
      }
      await expect(paymentHeading).toBeVisible({ timeout: 8000 });
    }).toPass({ timeout: 30000 });
  }

  async loadersShouldBeHidden() {
    await expect(this.page.locator("#magewire-loader")).toBeHidden();
  }
}
