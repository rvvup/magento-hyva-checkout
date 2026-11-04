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

test("passes the bundle price, not 0.00, on a dynamic-price bundle", async ({
  page,
}) => {
  await new GoTo(page).product.bundle();
  const wrapper = page.locator("[data-product-id][data-amount]").filter({
    has: page.locator("#rvvup-zrf-widget-container"),
  });
  await expect(wrapper).toHaveCount(1);
  const amount = await wrapper.getAttribute("data-amount");
  expect(Number(amount)).toBeGreaterThan(0);
});

test("starts the widget when inline scripts are moved away from the wrapper", async ({
  page,
}) => {
  // Simulate a store that moves inline scripts to the end of <body>.
  await page.route("**/fusion-backpack.html", async (route) => {
    const response = await route.fetch();
    let html = await response.text();
    const re =
      /<script>\s*\(function \(\) \{\s*const container = [\s\S]*?<\/script>/;
    const match = html.match(re);
    expect(match).not.toBeNull();
    html = html.replace(re, "").replace("</body>", match[0] + "</body>");
    await route.fulfill({ response, body: html });
  });
  const errors = [];
  page.on("console", (m) => m.type() === "error" && errors.push(m.text()));
  await new GoTo(page).product.standard("medium-priced");
  const container = page.locator("#rvvup-zrf-widget-container");
  await expect(container).toBeVisible();
  await expect(container.getByText(/p\/m/).first()).toBeVisible();
  expect(errors.join("\n")).not.toContain("total is not available");
});

test("passes the VAT-inclusive price when catalogue prices exclude VAT", async ({
  page,
}) => {
  // 24-MB03 is stored as 825.00 ex-VAT and displayed as 990.00 inc. 20% VAT
  await new GoTo(page).product.standard("ex-vat");
  await expect(page.getByText("£990.00").first()).toBeVisible();
  const wrapper = page.locator("[data-product-id][data-amount]").filter({
    has: page.locator("#rvvup-zrf-widget-container"),
  });
  await expect(wrapper).toHaveAttribute("data-amount", "990.00");
  await expect(page.locator("#rvvup-zrf-widget-container")).toBeVisible();
  await expect(
    page.locator("#rvvup-zrf-widget-container").getByText(/p\/m/).first(),
  ).toBeVisible();
});

test("updates the widget when bundle options change", async ({ page }) => {
  await new GoTo(page).product.bundle();
  const container = page.locator("#rvvup-zrf-widget-container");

  // Hyva fires update-product-final-price / update-bundle-option-prices for
  // bundles, not update-prices-<productId>.
  const firstRadio = page
    .locator("input[type=radio][name='bundle_option[1]']")
    .first();
  const qty = page.locator("input[name='bundle_option_qty[1]']");
  await firstRadio.check({ force: true });

  await qty.fill("3");
  await expect(container).toBeVisible();
  await expect.poll(() => monthlyPrice(container)).toMatch(/p\/m/);
  const monthlyAtThree = await monthlyPrice(container);

  await qty.fill("4");
  await expect.poll(() => monthlyPrice(container)).not.toBe(monthlyAtThree);
});
