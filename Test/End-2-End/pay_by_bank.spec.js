import { test, expect } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

test("Can place an order using pay by bank", async ({ page, browser }) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByLabel("Pay by Bank").click();
  /** Add timeout to prevent clicking 'Place Order' too fast, which will result in
   * failure to open popup modal
   */
  await page.waitForTimeout(3000);
  await page.getByRole("button", { name: "Place order" }).click();

  // Credit card form
  const frame = page.frameLocator("iframe.rvvup-modal");
  await frame.getByLabel("Mock Bank").click();
  await frame.getByRole("button", { name: "Log in on this device" }).click();

  await page.waitForURL("**/checkout/onepage/success/");

  await expect(
    page.getByRole("heading", { name: "Thank you for your purchase!" }),
  ).toBeVisible();

  const warningMessage = await page.$eval(
    ".message.warning",
    (el) => el.textContent,
  );
  expect(warningMessage).toContain(
    "Your payment is being processed and is pending confirmation. You will receive an email confirmation when the payment is confirmed.",
  );
});
