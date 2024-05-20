import { test, expect } from '@playwright/test';
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import Cart from "./Components/Cart";

test('Can place a Clearpay order', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visitAsClearpayUser();

    await page.getByLabel('Clearpay').click();

    await page.getByRole('button', { name: 'Place order' }).click();

    await visitCheckoutPayment.loadersShouldBeHidden();

    const frame = page.frameLocator('iframe.rvvup-modal');
    await frame.getByRole('button', { name: 'Accept All'}).click();

    await frame.getByTestId('login-password-input').fill('XHvZsaUWh6K-BPWgXY!NJBwG');
    await frame.getByRole('button', { name: 'Continue'}).click();
    await frame.getByRole('button', { name: 'Confirm'}).click();

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test('Renders the Clearpay widget on the product page', async ({ page }) => {
    await page.goto('./joust-duffle-bag.html');

    await expect(page.locator('.afterpay-modal-overlay')).toBeHidden();

    await expect(page.getByLabel('Clearpay logo - Opens a dialog')).toBeVisible();

    await page.getByLabel('Clearpay logo - Opens a dialog').click();
    await expect(page.locator('.afterpay-modal-overlay')).toBeVisible();
});

test('Renders the Clearpay widget on the cart page', async ({ page }) => {
    await new Cart(page).addStandardItemToCart();

    await page.goto('/checkout/cart');

    await expect(page.locator('.afterpay-modal-overlay')).toBeHidden();

    await expect(page.getByLabel('Clearpay logo - Opens a dialog')).toBeVisible();

    await page.getByLabel('Clearpay logo - Opens a dialog').click();
    await expect(page.locator('.afterpay-modal-overlay')).toBeVisible();
});

// TODO: Add test to check Clearpay infographics update when basket contents change

// TODO: Add test once we have a Clearpay-restricted product to test against
test.skip('Clearpay not available for restricted products', async ({ page }) => {
    await page.goto('./rvvup-crypto-future.html');

    await expect(page.getByText('This item has restrictions so not all payment methods may be available')).toBeVisible();

    await expect(page.getByRole('button', { name: 'Clearpay logo - Opens a dialog'})).not.toBeVisible();
});

// TODO: Test that Clearpay does not show when item or basket size is below threshold
test.skip('Clearpay not available for products below price threshold', async ({ page }) => {
    await page.goto('./affirm-water-bottle.html');
    
    await expect(page.getByText('This item has restrictions so not all payment methods may be available')).not.toBeVisible();

    await expect(page.getByRole('button', { name: 'Clearpay logo - Opens a dialog'})).not.toBeVisible();
});

// TODO: Test that Clearpay does not show when item or basket size exceeds thresholds
test.skip('Clearpay not available for products above price threshold', async ({ page }) => {
    await page.goto('./zing-jump-rope.html');
    
    await expect(page.getByText('This item has restrictions so not all payment methods may be available')).not.toBeVisible();

    await expect(page.getByRole('button', { name: 'Clearpay logo - Opens a dialog'})).not.toBeVisible();
});

test('Clearpay shows correct instalment amounts on product page', async ({ page }) => {
    await page.goto('./joust-duffle-bag.html');
    await expect(page.getByText('or 4 interest-free payments of £8.50 with')).toBeVisible();
});
