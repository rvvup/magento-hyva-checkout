import { test, expect } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

// Skipped for now: these require the Rvvup account to have Apple Pay configured
// as the INLINE flow. Re-enable once the account setting is verified.
test.skip("The inline Apple Pay button renders after navigating to the payment step", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByRole("radio", { name: "Apple Pay" }).click();

  await visitCheckoutPayment.loadersShouldBeHidden();

  await expect(page.locator("#rvvup-apple-pay-button")).toBeVisible();
});

test.skip("keeps the native place order button hidden when returning to payment with Apple Pay selected", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  const placeOrderButton = page
    .locator(".checkout-nav-main button.btn-primary")
    .first();

  await page.getByRole("radio", { name: "Apple Pay" }).click();
  await visitCheckoutPayment.loadersShouldBeHidden();
  await expect(page.locator("#rvvup-apple-pay-button")).toBeVisible();
  await expect(placeOrderButton).toBeHidden();

  await page
    .getByRole("navigation", { name: "Breadcrumb" })
    .getByRole("button", { name: "Shipping" })
    .click();
  await visitCheckoutPayment.loadersShouldBeHidden();

  await page
    .getByRole("button", { name: "Proceed to review & payments" })
    .click();
  await expect(
    page.getByRole("heading", { name: "Payment Method" }),
  ).toBeVisible();
  await visitCheckoutPayment.loadersShouldBeHidden();

  await expect(page.locator("#rvvup-apple-pay-button")).toBeVisible();
  await expect(placeOrderButton).toBeHidden();
});
