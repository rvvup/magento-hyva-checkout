import { test, expect } from '@playwright/test';
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

test('Can place an order using PayPal', async ({ page, browser }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByLabel('PayPal', { exact: true }).click();

    await expect(page.locator('#rvvup-paypal-button-container')).toBeVisible();

    page.on('popup', async popup => {
        await popup.waitForLoadState();

        await popup.getByPlaceholder('Email').fill('sb-uqeqf29136249@personal.example.com');
        await popup.getByPlaceholder('Password').fill('h5Hc/b8M');

        await popup.getByRole('button', { name: 'Log In' }).click();
        await popup.getByRole('button', { name: 'Complete Purchase' }).click();
    });

    const paypalFrame = page.frameLocator('#rvvup-paypal-button-container iframe:first-of-type')
    await paypalFrame.getByRole('link', { name: 'PayPal' }).click();

    await expect(page.locator('#payment-method-view-rvvup_PAYPAL'))
        .not.toContainText('You are currently paying with PayPal. If you want to cancel this process');

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test('Can place an order from the product page using PayPal', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);

    await page.goto('./joust-duffle-bag.html');

    page.on('popup', async popup => {
        await popup.waitForLoadState();

        await popup.getByPlaceholder('Email').fill('sb-uqeqf29136249@personal.example.com');
        await popup.getByRole('button', { name: 'Next' }).click();

        await popup.getByPlaceholder('Password').fill('h5Hc/b8M');
        await popup.getByRole('button', { name: 'Log In' }).click();

        await popup.getByRole('button', { name: 'Continue to review order' }).click();
    });

    const paypalFrame = page.frameLocator('.rvvup-paypal-express-button-container iframe:first-of-type')
    await paypalFrame.getByRole('link', { name: 'PayPal' }).click();

    await page.waitForURL("**/checkout/");

    await page.getByLabel('Phone number').fill('+447500000000');

    await page.getByLabel('Fixed').click();

    visitCheckoutPayment.loadersShouldBeHidden();

    await page.getByRole('button', { name: 'Proceed to review & payments' }).click();

    await expect(page.getByRole('heading', { name: 'Payment Method' })).toBeVisible();

    await expect(page.locator('#payment-method-view-rvvup_PAYPAL'))
        .toContainText('You are currently paying with PayPal. If you want to cancel this process');

    const children = await page.$$('#payment-methods ol > li');
    await expect(children.length).toBe(1);

    await page.getByRole('button', { name: 'Place order' }).click();
    await page.waitForURL("**/checkout/onepage/success/");
    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});
