echo "Running configure-rvvup.sh"

cd /bitnami/magento
rm -rf generated/

if [ "$RVVUP_HYVA_CHECKOUT_VERSION" == "local" ]; then
    # Run the command for "local"
    echo "Running local version setup..."
    mkdir -p app/code/Rvvup/PaymentsHyvaCheckout/src

else
    # Run the command for other values
    echo "Running setup for version: $RVVUP_HYVA_CHECKOUT_VERSION"
    composer require rvvup/module-magento-payments-hyva-checkout:$RVVUP_HYVA_CHECKOUT_VERSION
fi
