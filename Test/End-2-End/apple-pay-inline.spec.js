import { test, expect } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

test("The inline Apple Pay button renders after navigating to the payment step", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByRole("radio", { name: "Apple Pay" }).click();

  await visitCheckoutPayment.loadersShouldBeHidden();

  await expect(page.locator("#rvvup-apple-pay-button")).toBeVisible();
});
