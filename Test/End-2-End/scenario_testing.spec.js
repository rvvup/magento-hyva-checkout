import { test, expect } from '@playwright/test';
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import PayByBankCheckout from './Components/PayByBankCheckout';

test('Can switch between payment methods', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    // Switch to card
    await page.getByLabel('Pay by Card').click();
    await visitCheckoutPayment.loadersShouldBeHidden();
    await expect(page.locator('#rvvup-paypal-button-container')).toBeHidden();
    await expect(page.locator('#rvvup-card-form')).toBeVisible();

    await page.getByLabel('PayPal', { exact: true }).click();
    await visitCheckoutPayment.loadersShouldBeHidden();
    await expect(page.locator('#rvvup-card-form')).toBeHidden();
    await expect(page.locator('#rvvup-paypal-button-container')).toBeVisible();

    // Switch back to card
    await page.getByLabel('Pay by Card').click();
    await visitCheckoutPayment.loadersShouldBeHidden();
    await expect(page.locator('#rvvup-paypal-button-container')).toBeHidden();
    await expect(page.locator('#rvvup-card-form')).toBeVisible();
});

test('Can place an order using different billing and shipping address', async ({ page }) => {
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

test('Clear payment sessions between payment attempts on Hyva', async ({ page }) => {
    test.setTimeout(60000);
    
    await new VisitCheckoutPayment(page).visit();
    await new PayByBankCheckout(page).checkout();
    
    // On the 2nd attempt, the webhook of the 1st successful payment should not be picked up
    await new VisitCheckoutPayment(page).visit();
    
    await page.getByText('Rvvup Payment Method').click();
    await page.getByRole('button', { name: 'Place Order' }).click();
    await page.frameLocator('iframe').getByRole('button', { name: 'Pay now' }).click();
    
    await page.waitForURL("**/checkout/onepage/success/");
    
    await expect(page.getByText('Payment was already completed')).not.toBeVisible();
    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
})