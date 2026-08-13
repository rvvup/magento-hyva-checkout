import { test, expect } from "@playwright/test";
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import RvvupCardForm from "./Components/PaymentMethods/RvvupCardForm";
import CheckoutSuccess from "./Pages/CheckoutSuccess";
import Admin from "./Components/Admin";
import getCardFlow, { INLINE } from "./Components/CardFlow";
import clickCheckoutControl from "./Components/CheckoutControl";

// These cover the inline card flow driven by the Rvvup JS SDK. On a hosted/modal merchant the card
// form never renders, so card-modal.spec.js applies instead.
test.beforeEach(async ({ page }) => {
  test.skip(
    (await getCardFlow(page)) !== INLINE,
    "the merchant does not have the card INLINE flow",
  );
});

const selectCard = async (page, visitCheckoutPayment) => {
  await clickCheckoutControl(page.getByLabel("Pay by Card"));
  await visitCheckoutPayment.loadersShouldBeHidden();
};

test("Can place an order using the inline credit card", async ({ page }) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await selectCard(page, visitCheckoutPayment);

  const cardForm = new RvvupCardForm(page);
  await cardForm.fill();
  await cardForm.placeOrder();

  // The OTP form (3DS) does not always show.
  const frame = page.frameLocator("#Cardinal-CCA-IFrame");
  try {
    const element = frame.getByPlaceholder("Enter Code Here");
    await element.waitFor({ state: "visible", timeout: 10000 });
    await element.fill("1234");
    await element.press("Enter");
  } catch (error) {
    console.log("3DS form not found, so skipping it.");
  }

  await page.waitForURL("**/checkout/onepage/success/", { timeout: 60000 });

  const checkoutSuccess = new CheckoutSuccess(page);
  await checkoutSuccess.waitForSuccess();

  // Reaching the success page is not proof the payment landed: assert the order was actually paid
  // for rather than left behind in pending_payment.
  const orderNumber = await checkoutSuccess.getOrderNumber();
  await new Admin(page).expectOrderState(orderNumber, "processing");
});

// The SDK drops the whole appearance configuration unless it is nested under methodOptions, and it
// never writes border-style, so the card fields silently end up borderless and invisible against a
// white background. Both failure modes are silent, so assert the configured border actually renders.
test("it renders the card fields with a visible border", async ({ page }) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await selectCard(page, visitCheckoutPayment);

  const cardForm = new RvvupCardForm(page);
  await cardForm.shouldBeMounted();

  const border = await page
    .locator("#rvvup-card-number")
    .evaluate((element) => {
      const style = getComputedStyle(element);
      return {
        style: style.borderStyle,
        width: style.borderWidth,
        color: style.borderColor,
      };
    });

  expect(border.style).toBe("solid");
  expect(border.width).toBe("1px");
  // The configured colour, matching the inputs Hyva Checkout renders itself. Anything else means
  // the appearance was dropped and the SDK fell back to its own near invisible default.
  expect(border.color).toBe("rgb(202, 213, 226)");
});

// SUPPORT-186: submitting empty card fields used to show "Something went wrong, please try again".
// The shopper must get a message telling them what to correct, and the place order button has to
// stay usable so they can correct it.
test("it shows a meaningful message when the card fields are empty", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await selectCard(page, visitCheckoutPayment);

  const cardForm = new RvvupCardForm(page);
  await cardForm.shouldBeMounted();
  await cardForm.placeOrder();

  await expect(
    page.getByText("Please enter valid card details and try again."),
  ).toBeVisible();

  await expect(page.getByRole("button", { name: "Place Order" })).toBeEnabled();
});

// SUPPORT-187: the Magento order and the Rvvup payment session must only be created once the SDK has
// validated the card details, so a rejected attempt leaves no payment behind to reconcile.
test("it does not create a payment when the card details are invalid", async ({
  page,
}) => {
  const paymentSessionRequests = [];
  page.on("request", (request) => {
    if (
      request.method() === "POST" &&
      (request.postData() || "").includes("createPaymentSession")
    ) {
      paymentSessionRequests.push(request.url());
    }
  });

  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await selectCard(page, visitCheckoutPayment);

  const cardForm = new RvvupCardForm(page);
  await cardForm.shouldBeMounted();
  await cardForm.placeOrder();

  await expect(
    page.getByText("Please enter valid card details and try again."),
  ).toBeVisible();

  expect(paymentSessionRequests).toEqual([]);

  // No payment session request is only half the story: assert the backend is clean too, so a
  // rejected attempt can never leave a stranded order behind.
  await new Admin(page).expectNoOrderForCustomerEmail(
    visitCheckoutPayment.guestEmail,
  );
});

// SUPPORT-133 feedback: a failed authorization used to leave the shopper with "Something went wrong,
// please try again" and a place order button stuck spinning. The authorization is failed here rather
// than with a declining card because the sandbox clears the test cards, and because a decline
// currently reaches the plugin as an SDK `error` rather than a `paymentFailed`.
test("it shows a meaningful message and stays retryable when the authorization fails", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await selectCard(page, visitCheckoutPayment);

  await page.route(/\/card\/auth/, (route) => route.abort());

  const cardForm = new RvvupCardForm(page);
  await cardForm.fill();
  await cardForm.placeOrder();

  await expect(
    page.getByText(
      "Your payment could not be completed. Please check your details and try again.",
    ),
  ).toBeVisible({ timeout: 60000 });

  // The shopper has to be able to correct the payment without reloading the page: the loader must
  // be gone, the place order button usable again and the card fields still mounted.
  await expect(page.locator("#rvvup-loader > div")).toBeHidden();
  await expect(page.getByRole("button", { name: "Place Order" })).toBeEnabled();
  await cardForm.shouldBeMounted();

  // A failed authorization must not leave a paid looking order behind.
  const orders = await new Admin(page).getOrdersByCustomerEmail(
    visitCheckoutPayment.guestEmail,
  );
  expect(orders.map((order) => order.state)).not.toContain("processing");
});

// SUPPORT-188: changing an address field re-renders the payment step. The mounted SDK iframes have to
// survive that, otherwise the shopper has to reload the page before they can enter their card.
test("it keeps the card fields mounted after changing the address", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await selectCard(page, visitCheckoutPayment);

  const cardForm = new RvvupCardForm(page);
  await cardForm.shouldBeMounted();

  // Revealing the billing address re-renders the payment step around the mounted card fields.
  await page
    .getByLabel("My billing and shipping address are the same")
    .uncheck();
  await visitCheckoutPayment.loadersShouldBeHidden();

  await cardForm.shouldBeMounted();

  // Changing the country re-renders it again, this time with different address fields. The onepage
  // checkout also shows the shipping country, so the billing one is always the last.
  await page.getByLabel("Country").last().selectOption("Ireland");
  await visitCheckoutPayment.loadersShouldBeHidden();

  await cardForm.shouldBeMounted();
  await cardForm.fill();
});

// Switching payment method unmounts the card block entirely, so coming back has to mount a working
// form into the freshly rendered container rather than leaving an empty box behind.
test("it re-mounts the card fields after switching payment method and back", async ({
  page,
}) => {
  const visitCheckoutPayment = new VisitCheckoutPayment(page);
  await visitCheckoutPayment.visit();

  await selectCard(page, visitCheckoutPayment);

  const cardForm = new RvvupCardForm(page);
  await cardForm.shouldBeMounted();

  await page.getByLabel("PayPal", { exact: true }).click();
  await visitCheckoutPayment.loadersShouldBeHidden();
  await expect(cardForm.container()).toBeHidden();

  await selectCard(page, visitCheckoutPayment);

  await cardForm.shouldBeMounted();
  await cardForm.fill();
});
