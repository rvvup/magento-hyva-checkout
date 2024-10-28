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

  const frame = page.frameLocator("iframe.rvvup-modal");
  await frame.getByLabel("Mock Bank").click();
  await frame.getByRole("button", { name: "Continue on desktop" }).click();

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


test("Prevent close is handled on bank selection screen and can only be closed by pressing 'x'", async ({page}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByLabel("Pay by Bank").click();
  /** Add timeout to prevent clicking 'Place Order' too fast, which will result in
   * failure to open popup modal
   */
  await page.waitForTimeout(3000);
  await page.getByRole("button", {name: "Place order"}).click();

  const frame = page.frameLocator("iframe.rvvup-modal");
  await expect(frame.getByLabel("Mock Bank")).toBeVisible();
  await page.locator("#rvvup-modal div").first().click();
  // Shouldn't close
  await page.waitForTimeout(500);
  await frame.getByTitle("Exit").click();

  await expect(page.locator("#rvvup-modal div").first()).toBeHidden();
});
