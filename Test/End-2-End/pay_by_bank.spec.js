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

test("Prevent close is handled on bank selection screen and can only be closed by pressing 'x'", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await page.getByLabel("Pay by Bank").click();
  /** Add timeout to prevent clicking 'Place Order' too fast, which will result in
   * failure to open popup modal
   */
  await page.waitForTimeout(3000);
  await page.getByRole("button", { name: "Place order" }).click();

  const frame = page.frameLocator("iframe.rvvup-modal");
  await expect(frame.getByLabel("Mock Bank")).toBeVisible();

  const modalIframe = page.locator("#rvvup-modal iframe.rvvup-modal");

  // Clicking outside the dialog must NOT close it: prevent-close is active on the bank screen. The
  // modal is a top-layer native <dialog>, so a backdrop click is the light-dismiss the module must
  // suppress. A first click can be absorbed moving focus off the iframe, so click twice to make a
  // real dismiss attempt; the modal must still stay open.
  await page.mouse.click(5, 5);
  await page.mouse.click(5, 5);
  await page.waitForTimeout(500);
  await expect(modalIframe).toBeVisible();

  // Only the checkout's own Exit control closes it.
  await frame.getByTitle("Exit").click();
  await expect(modalIframe).toBeHidden();
});
