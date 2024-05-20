import {expect, test} from '@playwright/test';
import VisitCheckoutPayment from "./Pages/VisitCheckoutPayment";
import PaypalPopup from "./Components/PaypalPopup";

test('Can place an order using PayPal', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByLabel('PayPal', { exact: true }).click();

    await expect(page.locator('#rvvup-paypal-button-container')).toBeVisible();

    const popupPromise = page.waitForEvent('popup');
    const paypalFrame = page.frameLocator('#rvvup-paypal-button-container iframe').first();
    await paypalFrame.getByRole('link', { name: 'PayPal' }).click();

    await new PaypalPopup(await popupPromise).acceptPayment();

    await expect(page.locator('#payment-method-view-rvvup_PAYPAL'))
        .not.toContainText('You are currently paying with PayPal. If you want to cancel this process');
    await expect(page.frameLocator('#rvvup-modal iframe').getByText("Payment being processed")).toBeVisible();

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test.fixme('Can place an order using PayPal debit or credit cards', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByLabel('PayPal', { exact: true }).click();

    const paypalFrame = page.frameLocator("[title='PayPal']").first();
    await paypalFrame.getByRole('link', { name: 'Debit or Credit Card' }).click();

    const paypalCardForm = page.frameLocator("[title='paypal_card_form']");
    await paypalCardForm.getByLabel('Card number').fill('4698 4665 2050 8153')
    await paypalCardForm.getByLabel('Expires').fill('1125')
    await paypalCardForm.getByLabel('Security code').fill('141')
    await paypalCardForm.getByLabel('Mobile').fill('1234567890')
    await paypalCardForm.getByRole('button', { name: 'Buy Now' }).click();

    await page.waitForURL("**/checkout/onepage/success/");

    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test('Can place an order from the product page using PayPal', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);

    await page.goto('./joust-duffle-bag.html');

    const popupPromise = page.waitForEvent('popup');
    const paypalFrame = page.frameLocator('.rvvup-paypal-express-button-container iframe').first();
    await paypalFrame.getByRole('link', { name: 'PayPal' }).click();
    await new PaypalPopup(await popupPromise).acceptPayment();

    await page.waitForURL("**/checkout/");

    await page.getByLabel('Phone number').fill('+447500000000');

    await page.getByLabel('Fixed').click();

    await visitCheckoutPayment.loadersShouldBeHidden();

    await page.getByRole('button', { name: 'Proceed to review & payments' }).click();

    await expect(page.getByRole('heading', { name: 'Payment Method' })).toBeVisible();

    await expect(page.locator('#payment-method-view-rvvup_PAYPAL'))
        .toContainText('You are currently paying with PayPal. If you want to cancel this process');

    const children = await page.$$('#payment-methods ol > li');
    await expect(children.length).toBe(1);

    await page.getByRole('button', { name: 'Place order' }).click();
    await expect(page.frameLocator('#rvvup-modal iframe').getByText("Payment being processed")).toBeVisible();
    await page.waitForURL("**/checkout/onepage/success/");
    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test.fixme('Can place an order from the product page using PayPal debit or credit cards', async ({ page }) => {
    page.goto('./joust-duffle-bag.html');

    const paypalFrame = page.frameLocator("[title='PayPal']").first();
    await paypalFrame.getByRole('link', { name: 'Debit or Credit Card' }).click();

    // Fill in the form
    const paypalCardForm = paypalFrame.frameLocator("[title='paypal_card_form']"); 
    await paypalCardForm.getByPlaceholder('Card number').fill('4698 4665 2050 8153')
    await paypalCardForm.getByPlaceholder('Expires').fill('1125')
    await paypalCardForm.getByPlaceholder('Security code').fill('141')

    await paypalCardForm.getByPlaceholder('First name').fill('John');
    await paypalCardForm.getByPlaceholder('Last name').fill('Doe');
    await paypalCardForm.getByPlaceholder('Address line 1').fill('123 Main St');
    await paypalCardForm.getByPlaceholder('Town/City').fill('London');
    await paypalCardForm.getByPlaceholder('Postcode').fill('SW1A 1AA');
    await paypalCardForm.getByPlaceholder('Mobile').fill('1234567890')
    await paypalCardForm.getByPlaceholder('Email').fill('johndoe@example.com')

    await paypalCardForm.getByRole('button', { name: 'Continue' }).click();

    // Continue to shipping and checkout
    await page.getByLabel('Phone number').fill('+441234567890');
    await page.getByRole('button', { name: 'Next' }).click();

    await expect(page.getByText('Payment Method', { exact: true })).toBeVisible();

    await page.getByRole('button', { name: 'Place order' }).click();
    
    await page.waitForURL("**/checkout/onepage/success/");
    
    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();
});

test('PayPal replaces the Place Order button with a PayPal button', async ({ page }) => {
    const visitCheckoutPayment = new VisitCheckoutPayment(page);
    await visitCheckoutPayment.visit();

    await page.getByText('Pay by Bank', { exact: true }).click();
    await expect(page.getByRole('button', { name: 'Place Order' })).toBeVisible();
    
    await page.getByText('PayPal', { exact: true }).click();
    await expect(page.getByRole('button', { name: 'Place Order' })).not.toBeVisible();

    await expect(
        page.frameLocator("[title='PayPal']").first().getByRole('link', { name: 'PayPal' })
    ).toBeVisible();
})