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
## Dockerized Setup of Test Store

If you would like to have a quick local installation of the plugin on a magento store (for testing), you can follow these steps:

- Copy .env.sample to .env and update the values as needed.
- Run the following command to start the docker containers:
```
docker compose up -d --build
```
## End to End Testing
This plugin comes with Playwright tests to ensure it's functionality.

### Get Started (install dependencies):
```bash
npm i
npx playwright install
```

### (Recommended), Running the E2E tests against a dockerized store installation

This will spin up a docker container with magento with hyva + rvvup plugin installed and run the test against this
container.
```bash
./scripts/run-e2e-tests.sh
```

### If you have an existing store, to run the tests, use the following command:

```bash
ENV TEST_BASE_URL=https://magento.test npx playwright test --ui # change your base url to point to the right domain
```

**Please note:** There are tests included for credit card for both the inline and the modal versions. It depends on the configuration of the payment method which test will succeed.
