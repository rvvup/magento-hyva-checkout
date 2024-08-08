import { test, expect } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import Cart from "./Components/Cart";

// This test is quite far, but the Clearpay modal is quite a bit flakey. It sometimes works, sometimes doesn't.
test.skip("Can place an order", async ({ page, browser }) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByLabel("Clearpay").click();

  /** Add timeout to prevent clicking 'Place Order' too fast, which will result in
   * failure to open popup modal
   */
  await page.waitForTimeout(3000);

  await page.getByRole("button", { name: "Place order" }).click();

  await visitCheckoutPayment.loadersShouldBeHidden();

  const frame = page.frameLocator("iframe.rvvup-modal");
  await frame.getByRole("button", { name: "Accept All" }).click();

  const randomString =
    Math.random().toString(36).substring(2, 15) +
    Math.random().toString(36).substring(2, 15);
  await frame
    .getByLabel("Email or mobile number")
    .fill("playwright-" + randomString + "@example.com");
  await frame.getByRole("button", { name: "Continue" }).click();

  await frame
    .getByLabel("Mobile number")
    .fill("+777777" + Math.floor(Math.random() * 100000));
  await frame.getByLabel("Date of birth").fill("11/11/1985");
  await frame.getByTestId("sign-up-address-manual-button").click();
  await frame.getByLabel("Address 1").fill("7 Savoy Ct");
  await frame.getByLabel("Town / City").fill("London");
  await frame.getByLabel("County (Optional)").fill("Greater London");
  await frame.getByLabel("Postcode").fill("WC2R 0EZ");

  await frame.getByRole("button", { name: "Continue" }).click();

  await frame
    .getByTestId("verify-code-entry-input")
    .fill("111111", { force: true });

  await frame
    .getByTestId("payment-method-cardNumber-input")
    .fill("4111 1111 1111 1111");
  await frame.getByTestId("payment-method-cardExpiry-input").fill("1233");
  await frame.getByTestId("payment-method-cardCvv-input").fill("123");
  await frame.getByTestId("add-payment-method-button").click();
  await frame.getByTestId("add-payment-method-button").click();

  await frame.getByTestId("summary-button").click();

  await page.waitForURL("**/checkout/onepage/success/");

  await expect(
    page.getByRole("heading", { name: "Thank you for your purchase!" }),
  ).toBeVisible();
});

test("Renders the Clearpay on the product page", async ({ page }) => {
  await page.goto("./joust-duffle-bag.html");

  await expect(page.locator(".afterpay-modal-overlay")).toBeHidden();

  await expect(page.getByLabel("Clearpay logo - Opens a dialog")).toBeVisible();

  await page.getByLabel("Clearpay logo - Opens a dialog").click();
  await expect(page.locator(".afterpay-modal-overlay")).toBeVisible();
});

test("Renders the Clearpay widget in the checkout", async ({ page }) => {
  await new Cart(page).addStandardItemToCart();

  await page.goto("/checkout/cart");

  await expect(page.locator(".afterpay-modal-overlay")).toBeHidden();

  await expect(page.getByLabel("Clearpay logo - Opens a dialog")).toBeVisible();

  await page.getByLabel("Clearpay logo - Opens a dialog").click();
  await expect(page.locator(".afterpay-modal-overlay")).toBeVisible();
});
