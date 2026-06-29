import { expect, test } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import PaypalPopup from "./Components/PaypalPopup";
import GoTo from "./Components/GoTo";

test("hides the native place order button while PayPal is selected and restores it for other methods", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  const placeOrderButton = page
    .locator(".checkout-nav-main button.btn-primary")
    .first();

  await expect(placeOrderButton).toBeVisible();

  await page.getByLabel("PayPal", { exact: true }).click();
  await visitCheckoutPayment.loadersShouldBeHidden();
  await expect(page.locator("#rvvup-paypal-button-container-0")).toBeVisible();

  await expect(placeOrderButton).toBeHidden();

  await page.getByLabel("Pay by Card").click();
  await visitCheckoutPayment.loadersShouldBeHidden();
  await expect(page.locator("#rvvup-paypal-button-container-0")).toBeHidden();
  await expect(placeOrderButton).toBeVisible();
});

test("Can place an order using PayPal", async ({ page, browser }) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByLabel("PayPal", { exact: true }).click();

  await expect(page.locator("#rvvup-paypal-button-container-0")).toBeVisible();
  await page.waitForTimeout(2000);

  const popupPromise = page.waitForEvent("popup");
  const paypalFrame = page
    .frameLocator("#rvvup-paypal-button-container-0 iframe")
    .first();
  await paypalFrame.getByRole("link", { name: "PayPal" }).click();

  await new PaypalPopup(await popupPromise).acceptPayment();

  await expect(
    page.locator("#payment-method-view-rvvup_PAYPAL"),
  ).not.toContainText(
    "You are currently paying with PayPal. If you want to cancel this process",
  );

  await page.waitForURL("**/checkout/onepage/success/");

  await expect(
    page.getByRole("heading", { name: "Thank you for your purchase!" }),
  ).toBeVisible();
});

test("Can place an order from the product page using PayPal", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);

  await new GoTo(page).product.standard("medium-priced");

  const popupPromise = page.waitForEvent("popup");
  const paypalFrame = page
    .frameLocator(".rvvup-paypal-express-button-container iframe")
    .first();
  await paypalFrame.getByRole("link", { name: "PayPal" }).click();
  await new PaypalPopup(await popupPromise).acceptPayment();

  await page.waitForURL("**/checkout/");

  await page.getByLabel("Phone number").fill("+447500000000");

  await page.locator('input[name="shipping-method-option"]').first().click();
  await visitCheckoutPayment.loadersShouldBeHidden();

  await page.waitForTimeout(2000);

  await visitCheckoutPayment.loadersShouldBeHidden();

  await page.getByRole('button', { name: 'Proceed to Review & Payments' }).click();

  await expect(
    page.getByRole("heading", { name: "Payment Method" }),
  ).toBeVisible();

  await expect(page.locator("#payment-method-view-rvvup_PAYPAL")).toContainText(
    "You are currently paying with PayPal. If you want to cancel this process",
  );

  const children = await page.$$(
    "#payment-methods ol > li, #payment-method-list > div",
  );
  await expect(children.length).toBe(1);

  await page.getByRole("button", { name: "Place order" }).click();
  await page.waitForURL("**/checkout/onepage/success/");
  await expect(
    page.getByRole("heading", { name: "Thank you for your purchase!" }),
  ).toBeVisible();
});

async function selectSwatch(page, label) {
  await page
    .locator(`.swatch-option:has(input[aria-label="` + label + `"])`)
    .click();
  await expect(page.locator(`input[aria-label="` + label + `"]`)).toBeChecked();
}

test("renders the paypal widget on the standard product page", async ({
  page,
}) => {
  await new GoTo(page).product.standard("medium-priced");

  await expect(
    page
      .frameLocator(".rvvup-paypal-paylater-messaging-container iframe")
      .first()
      .getByText("Pay in 3 interest-free payments of £50.00"),
  ).toBeVisible();
});

test("renders the paypal widget on the configurable product page", async ({
  page,
}) => {
  await new GoTo(page).product.configurable();

  await selectSwatch(page, "XS");

  await selectSwatch(page, "Black");

  await expect(
    page
      .frameLocator(".rvvup-paypal-paylater-messaging-container iframe")
      .first()
      .getByText("Pay in 3 interest-free payments of £15.00"),
  ).toBeVisible();

  await selectSwatch(page, "S");

  await expect(
    page
      .frameLocator(".rvvup-paypal-paylater-messaging-container iframe")
      .first()
      .getByText("Pay in 3 interest-free payments of £33.34"),
  ).toBeVisible();
});
