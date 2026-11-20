import { test, expect } from "@playwright/test";
import Cart from "./Components/Cart";

// The payment modal is rendered on every checkout step for both the hosted and the inline card
// flow, so this applies to any store configuration.
test("it keeps the payment modal out of the page until a payment opens it", async ({
  page,
}) => {
  await new Cart(page).addStandardItemToCart();
  await page.goto("./checkout");

  const dialog = page.locator("#rvvup-modal dialog");
  await expect(dialog).toBeAttached();

  // A closed dialog is hidden by the browser its own dialog:not([open]) rule, which a plain
  // display:flex would override and leave an empty box sitting in the checkout.
  await expect(dialog).toBeHidden();

  const modalHeight = await page
    .locator("#rvvup-modal")
    .evaluate((element) => element.getBoundingClientRect().height);

  expect(modalHeight).toBe(0);
});
