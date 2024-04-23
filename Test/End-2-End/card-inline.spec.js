import { test, expect } from '@playwright/test';
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";

test('Can place an order using the inline credit card', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByLabel('Pay by Card').click();

    await visitCheckoutPayment.loadersShouldBeHidden();

    // Credit card form
    await page.frameLocator('.st-card-number-iframe').getByLabel('Card Number').fill('4111 1111 1111 1111');
    await page.frameLocator('.st-expiration-date-iframe').getByLabel('Expiration Date').fill('1233');
    await page.frameLocator('.st-security-code-iframe').getByLabel('Security Code').fill('123');
    await page.getByRole('button', { name: 'Place order' }).click();

    await visitCheckoutPayment.loadersShouldBeHidden();

    // OTP form
    await page.frameLocator('#Cardinal-CCA-IFrame').getByPlaceholder('Enter Code Here').fill('1234');
    await page.frameLocator('#Cardinal-CCA-IFrame').getByPlaceholder('Enter Code Here').press('Enter');

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test('The validation prevents placing an order with invalid card details', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByLabel('Pay by Card').click();

    await expect(page.locator('#rvvup-card-form')).toBeVisible();

    await visitCheckoutPayment.loadersShouldBeHidden();

    await expect(page.locator('.st-security-code-iframe')).toBeVisible();

    await page.getByRole('button', { name: 'Place order' }).click();

    await expect(page.frameLocator('iframe[name="st-expiration-date-iframe"]').getByText('Field is required')).toBeVisible();
});
