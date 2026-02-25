import { expect, test } from "@playwright/test";
import GoTo from "./Components/GoTo";

async function selectSwatch(page, label) {
  await page
    .locator(`.swatch-option:has(input[aria-label="` + label + `"])`)
    .click();
  await expect(page.locator(`input[aria-label="` + label + `"]`)).toBeChecked();
}
test("renders the ZRF widget on the configurable product page", async ({
  page,
}) => {
  await new GoTo(page).product.configurable();

  await expect(page.locator("#rvvup-zrf-widget-container")).toBeHidden();

  await selectSwatch(page, "XS");

  await selectSwatch(page, "Black");

  await expect(page.locator("#rvvup-zrf-widget-container")).toBeHidden();

  await selectSwatch(page, "S");

  await expect(page.locator("#rvvup-zrf-widget-container")).toBeVisible();

  await expect(
    page
      .locator("#rvvup-zrf-widget-container")
      .getByText("Or from £4.17 p/m, at 0.00%"),
  ).toBeVisible();

  await selectSwatch(page, "XL");

  await expect(page.locator("#rvvup-zrf-widget-container")).toBeHidden();
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

  await expect(page.locator("#rvvup-zrf-widget-container")).toBeVisible();

  await expect(
    page
      .locator("#rvvup-zrf-widget-container")
      .getByText("Or from £6.25 p/m, at 0.00%"),
  ).toBeVisible();
});
