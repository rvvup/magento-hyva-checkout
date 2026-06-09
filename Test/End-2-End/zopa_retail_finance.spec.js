import { expect, test } from "@playwright/test";
import GoTo from "./Components/GoTo";

async function selectSwatch(page, label) {
  await page
    .locator(`.swatch-option:has(input[aria-label="` + label + `"])`)
    .click();
  await expect(page.locator(`input[aria-label="` + label + `"]`)).toBeChecked();
}

async function readMonthlyPrice(container) {
  const text = await container
    .getByText(/Or from £[\d,]+\.\d+ p\/m/)
    .first()
    .innerText();
  return parseFloat(text.match(/£([\d,]+\.\d+)\s*p\/m/)[1].replace(/,/g, ""));
}

test("updates the ZRF widget price when the selected swatch changes", async ({
  page,
}) => {
  await new GoTo(page).product.configurable();
  const container = page.locator("#rvvup-zrf-widget-container");

  await selectSwatch(page, "Black");

  // XS (£45) is below the finance threshold, so the widget stays hidden
  await selectSwatch(page, "XS");
  await expect(container).toBeHidden();

  // S (£100) qualifies, so the widget shows a monthly price
  await selectSwatch(page, "S");
  await expect(container).toBeVisible();
  const monthlyForS = await readMonthlyPrice(container);

  // XL (£90,000) is far more expensive, so the widget must update to a higher monthly price.
  // Asserting the price increased (rather than matching any price) catches the widget failing to
  // update when the swatch changes.
  await selectSwatch(page, "XL");
  await expect(container).toBeVisible();
  await expect
    .poll(() => readMonthlyPrice(container), { timeout: 20000 })
    .toBeGreaterThan(monthlyForS);
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
      container.getByText(/Or from £\d+\.\d+ p\/m, at \d+\.\d+%/),
  ).toBeVisible();
});
