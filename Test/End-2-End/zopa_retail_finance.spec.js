import { expect, test } from "@playwright/test";
import GoTo from "./Components/GoTo";

async function selectSwatch(page, label) {
  await page
    .locator(`.swatch-option:has(input[aria-label="` + label + `"])`)
    .click();
  await expect(page.locator(`input[aria-label="` + label + `"]`)).toBeChecked();
}

async function monthlyPrice(container) {
  const text = await container.getByText(/p\/m/).first().textContent();
  return text.match(/£\d+\.\d{2} p\/m/)[0];
}

test("updates the ZRF widget visibility when the selected swatch changes", async ({
  page,
}) => {
  await new GoTo(page).product.configurable();
  const container = page.locator("#rvvup-zrf-widget-container");

  await selectSwatch(page, "Black");

  // XS (£45) is below the finance minimum, so the widget stays hidden
  await selectSwatch(page, "XS");
  await expect(container).toBeHidden();

  // M (£54) qualifies, so the widget shows a monthly price
  await selectSwatch(page, "M");
  await expect(container).toBeVisible();
  const priceAtMedium = await monthlyPrice(container);

  // S (£100) also qualifies and must recompute to a different monthly price,
  // proving the widget updates its pricing when the selection changes
  await selectSwatch(page, "S");
  await expect(container).toBeVisible();
  await expect.poll(() => monthlyPrice(container)).not.toBe(priceAtMedium);

  // XL (£90,000) is above the finance maximum, so the widget hides again
  await selectSwatch(page, "XL");
  await expect(container).toBeHidden();
});

test("does not render on standard product page for cheap product", async ({
  page,
}) => {
  await new GoTo(page).product.standard("cheap");

  await page.waitForTimeout(1000);

  await expect(page.locator("#rvvup-zrf-widget-container")).toBeHidden();
});

test("renders the ZRF widget on the standard product page", async ({
  page,
}) => {
  await new GoTo(page).product.standard("medium-priced");
  await page.waitForTimeout(2000);
  const container = page.locator("#rvvup-zrf-widget-container");
  await expect(container).toBeVisible();
  await expect(
      container.getByText(/Or from £\d+\.\d{2} p\/m, at \d+\.\d{2}%/),
  ).toBeVisible();
});
