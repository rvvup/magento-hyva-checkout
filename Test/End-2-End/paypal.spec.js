import { expect, test } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import PaypalPopup from "./Components/PaypalPopup";

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
  await expect(
    page
      .frameLocator("#rvvup-modal iframe")
      .getByText("Payment being processed"),
  ).toBeVisible();

  await page.waitForURL("**/checkout/onepage/success/");

  await expect(
    page.getByRole("heading", { name: "Thank you for your purchase!" }),
  ).toBeVisible();
});

test("Can place an order from the product page using PayPal", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);

  await page.goto("./joust-duffle-bag.html");

  const popupPromise = page.waitForEvent("popup");
  const paypalFrame = page
    .frameLocator(".rvvup-paypal-express-button-container iframe")
    .first();
  await paypalFrame.getByRole("link", { name: "PayPal" }).click();
  await new PaypalPopup(await popupPromise).acceptPayment();

  await page.waitForURL("**/checkout/");

  await page.getByLabel("Phone number").fill("+447500000000");

  await page.getByLabel("Fixed").click();
  await visitCheckoutPayment.loadersShouldBeHidden();

  await page.waitForTimeout(2000);

  await visitCheckoutPayment.loadersShouldBeHidden();

  await page
    .getByRole("button", { name: "Proceed to review & payments" })
    .click();

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
  await expect(
    page
      .frameLocator("#rvvup-modal iframe")
      .getByText("Payment being processed"),
  ).toBeVisible();
  await page.waitForURL("**/checkout/onepage/success/");
  await expect(
    page.getByRole("heading", { name: "Thank you for your purchase!" }),
  ).toBeVisible();
});
