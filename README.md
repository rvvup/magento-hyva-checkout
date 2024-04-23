# Rvvup Payment Methods Plugin for Hyva Checkout

## What's This?

This plugin integrates Rvvup payment solutions seamlessly into the Hyva Checkout process for Magento 2. Leveraging the power of Rvvup's versatile payment options, it offers a streamlined, secure, and user-friendly payment experience for customers. It's designed to work out of the box with the Hyva Checkout, enhancing its functionality with minimal setup.

## Installation

To install the plugin, you can use the following commands:

```bash
composer require rvvup/module-magento-payments-hyva-checkout
```

After that, run setup:upgrade to install the plugin:

```bash
bin/magento setup:upgrade
```

## Testing

This plugin comes with Playwright tests to ensure its functionality. To run the tests, use the following command:

```bash
npm ci # Install the required dependencies
ENV TEST_BASE_URL=https://magento.test npx playwright test --ui # change your base url to point to the right domain
```

**Please note:** There are tests included for credit card for both the inline and the modal versions. It depends on the configuration of the payment method which test will succeed.
