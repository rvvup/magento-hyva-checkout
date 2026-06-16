import { expect, test } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import Cart from "./Components/Cart";
import { v7 as uuidv7 } from "uuid";
import GoTo from "./Components/GoTo";

async function selectSwatch(page, label) {
  await page
    .locator(`.swatch-option:has(input[aria-label="` + label + `"])`)
    .click();
  await expect(page.locator(`input[aria-label="` + label + `"]`)).toBeChecked();
}
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

  await frame
    .getByLabel("Email or mobile number")
    .fill(uuidv7() + "@example.com");
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

test("re-enables Place order after the Clearpay modal is cancelled and a method is switched", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByLabel("Clearpay").click();
  await visitCheckoutPayment.loadersShouldBeHidden();

  const placeOrder = page.getByRole("button", { name: "Place order" });
  await expect(placeOrder).toBeEnabled();

  // Clicking too fast can fail to open the modal, mirroring the manual repro.
  await page.waitForTimeout(3000);
  await placeOrder.click();
  await visitCheckoutPayment.loadersShouldBeHidden();

  const modalIframe = page.locator("#rvvup-modal iframe.rvvup-modal");
  await expect(modalIframe).toBeVisible();

  // Cancel by clicking out of the modal.
  await page.keyboard.press("Escape");
  await expect(modalIframe).toBeHidden();

  // Place order must be usable again after cancelling the modal.
  await expect(placeOrder).toBeEnabled();

  // Switching to another method must also leave Place order usable.
  await page.getByLabel("Pay by Card").click();
  await visitCheckoutPayment.loadersShouldBeHidden();

  await expect(page.getByRole("button", { name: "Place order" })).toBeEnabled();
});

test("renders the Clearpay on the product page", async ({ page }) => {
  await new GoTo(page).product.standard("medium-priced");
  await expect(
    page
      .locator("#clearpay-summary")
      .getByText("or 4 interest-free payments of £37.50"),
  ).toBeVisible();

  await expect(page.locator(".afterpay-modal-overlay")).toBeHidden();

  await expect(page.getByLabel("Clearpay logo - Opens a dialog")).toBeVisible();

  await page.getByLabel("Clearpay logo - Opens a dialog").click();
  await expect(page.locator(".afterpay-modal-overlay")).toBeVisible();
});

test("renders the Clearpay widget on the configurable product page", async ({
  page,
}) => {
  await new GoTo(page).product.configurable();

  await selectSwatch(page, "XS");

  await selectSwatch(page, "Black");

  await expect(
    page
      .locator("#clearpay-summary")
      .getByText("or 4 interest-free payments of £11.25"),
  ).toBeVisible();

  await selectSwatch(page, "S");

  await expect(
    page
      .locator("#clearpay-summary")
      .getByText("or 4 interest-free payments of £25.00"),
  ).toBeVisible();
});

test("renders the Clearpay widget in the checkout", async ({ page }) => {
  await new Cart(page).addStandardItemToCart();

  await new GoTo(page).cart();

  await expect(page.locator(".afterpay-modal-overlay")).toBeHidden();

  await expect(page.getByLabel("Clearpay logo - Opens a dialog")).toBeVisible();

  await page.getByLabel("Clearpay logo - Opens a dialog").click();
  await expect(page.locator(".afterpay-modal-overlay")).toBeVisible();
});
