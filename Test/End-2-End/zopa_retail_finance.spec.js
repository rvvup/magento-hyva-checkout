import { expect, test } from "@playwright/test";
import GoTo from "./Components/GoTo";

async function selectSwatch(page, label) {
  await page
    .locator(`.swatch-option:has(input[aria-label="` + label + `"])`)
    .click();
  await expect(page.locator(`input[aria-label="` + label + `"]`)).toBeChecked();
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

  // S (£100) qualifies, so the widget shows a monthly price
  await selectSwatch(page, "S");
  await expect(container).toBeVisible();
  await expect(
    container
      .getByText("Or from £4.17 p/m, at 0.00%")
      .or(container.getByText("Or from £2.55 p/m, at 19.90%")),
  ).toBeVisible();

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
      container
          .getByText("Or from £6.25 p/m, at 0.00%")
          .or(container.getByText("Or from £3.83 p/m, at 19.90%")),
  ).toBeVisible();
});
