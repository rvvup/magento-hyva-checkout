import { test, expect } from '@playwright/test';
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

test('Can place an order using different billing and shipping address', async ({ page, browser }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();
    await page.getByLabel('My billing and shipping address are the same').setChecked(false);

    await page.getByLabel('First name').fill('Liam');
    await page.getByLabel('Last name').fill('Fox');
    await page.getByLabel('Street address').fill('123 Small St');
    await page.getByLabel('Form Field').fill('2nd Line');
    await page.getByLabel('City').fill('Derby');
    await page.getByLabel('Country').selectOption('United Kingdom');
    await page.getByLabel('ZIP').fill('SW1B 1BB');
    await page.getByLabel('Phone number').fill('+447599999999');

    await page.getByLabel('Pay by Bank').click();

    await page.getByRole('button', { name: 'Place order' }).click();

    // Credit card form
    const frame = page.frameLocator('iframe.rvvup-modal');
    await frame.getByLabel('Mock Bank').click();
    await frame.getByRole('button', { name: 'Log in on this device' }).click();

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();

    const warningMessage = await page.$eval('.message.warning', el => el.textContent);
    expect(warningMessage).toContain('Your payment is being processed and is pending confirmation. You will receive an email confirmation when the payment is confirmed.');
});

