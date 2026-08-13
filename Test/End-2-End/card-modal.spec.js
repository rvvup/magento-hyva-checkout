import { test } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import CheckoutSuccess from "./Pages/CheckoutSuccess";
import Admin from "./Components/Admin";
import getCardFlow, { INLINE } from "./Components/CardFlow";
import clickCheckoutControl from "./Components/CheckoutControl";

// The hosted modal only exists on a merchant without the card INLINE flow, where card-inline.spec.js
// is the one that cannot run.
test.beforeEach(async ({ page }) => {
  test.skip(
    (await getCardFlow(page)) === INLINE,
    "the merchant has the card INLINE flow",
  );
});

test("Can place an order using the credit card modal", async ({ page }) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await clickCheckoutControl(page.getByLabel("Pay by Card"));

  /** Add timeout to prevent clicking 'Place Order' too fast, which will result in
   * failure to open popup modal
   */
  await page.waitForTimeout(3000);

  await page.getByRole("button", { name: "Place order" }).click();

  // Credit card form
  const frame = page.frameLocator("iframe.rvvup-modal");
  await frame
    .frameLocator(".st-card-number-iframe")
    .getByLabel("Card Number")
    .fill("4111 1111 1111 1111");
  await frame
    .frameLocator(".st-expiration-date-iframe")
    .getByLabel("Expiration Date")
    .fill("1233");
  await frame
    .frameLocator(".st-security-code-iframe")
    .getByLabel("Security Code")
    .fill("123");
  await frame.getByRole("button", { name: "Submit" }).click();

  // The OTP form (3DS) does not always show.
  try {
    const element = frame
      .frameLocator("#Cardinal-CCA-IFrame")
      .getByPlaceholder("Enter Code Here");
    await element.waitFor({ state: "visible", timeout: 10000 });
    await element.fill("1234");
    await element.press("Enter");
  } catch (error) {
    console.log("3DS form not found, so skipping it.");
  }

  await page.waitForURL("**/checkout/onepage/success/", { timeout: 60000 });

  const checkoutSuccess = new CheckoutSuccess(page);
  await checkoutSuccess.waitForSuccess();

  const orderNumber = await checkoutSuccess.getOrderNumber();

  await new Admin(page).getOrderByIncrementId(orderNumber);
});
