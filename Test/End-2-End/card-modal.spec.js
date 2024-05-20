import { test, expect } from '@playwright/test';
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

// These tests are dependent on configuring the test store to use the modal card payment flow. Inline is the default

test('Can place a modal pay by card order with a 3DS challenge', async ({ page, browser }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByLabel('Pay by Card').click();

    await page.getByRole('button', { name: 'Place order' }).click();

    // Credit card form
    const frame = page.frameLocator('iframe.rvvup-modal');
    await frame.frameLocator('.st-card-number-iframe').getByLabel('Card Number').fill('4111 1111 1111 1111');
    await frame.frameLocator('.st-expiration-date-iframe').getByLabel('Expiration Date').fill('1233');
    await frame.frameLocator('.st-security-code-iframe').getByLabel('Security Code').fill('123');
    await frame.getByRole('button', { name: 'Submit'}).click();

    // OTP form
    await frame.frameLocator('#Cardinal-CCA-IFrame').getByPlaceholder('Enter Code Here').fill('1234');
    await frame.frameLocator('#Cardinal-CCA-IFrame').getByPlaceholder('Enter Code Here').press('Enter');

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test('Can place a modal pay by card order without 3DS challenge', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByLabel('Pay by Card').click();

    await page.getByRole('button', { name: 'Place order' }).click();

    const frame = page.frameLocator('iframe.rvvup-modal');
    await frame.frameLocator('.st-card-number-iframe').getByLabel('Card Number').fill('4000 0000 0000 2701');
    await frame.frameLocator('.st-expiration-date-iframe').getByLabel('Expiration Date').fill('1233');
    await frame.frameLocator('.st-security-code-iframe').getByLabel('Security Code').fill('123');
    await frame.getByRole('button', { name: 'Submit'}).click();

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});
