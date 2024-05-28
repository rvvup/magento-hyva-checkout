import { test, expect } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

test("Can switch between payment methods", async ({ page }) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  // Switch to card
  await page.getByLabel("Pay by Card").click();
  await visitCheckoutPayment.loadersShouldBeHidden();
  await expect(page.locator("#rvvup-paypal-button-container")).toBeHidden();
  await expect(page.locator("#rvvup-card-form")).toBeVisible();

  await page.getByLabel("PayPal", { exact: true }).click();
  await visitCheckoutPayment.loadersShouldBeHidden();
  await expect(page.locator("#rvvup-card-form")).toBeHidden();
  await expect(page.locator("#rvvup-paypal-button-container")).toBeVisible();

  // Switch back to card
  await page.getByLabel("Pay by Card").click();
  await visitCheckoutPayment.loadersShouldBeHidden();
  await expect(page.locator("#rvvup-paypal-button-container")).toBeHidden();
  await expect(page.locator("#rvvup-card-form")).toBeVisible();
});
