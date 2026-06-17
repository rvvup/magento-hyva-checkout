echo "Running configure-rvvup.sh"

cd /bitnami/magento
rm -rf generated/

if [ "$RVVUP_HYVA_CHECKOUT_VERSION" == "local" ]; then
    # Run the command for "local"
    echo "Running local version setup..."
    mkdir -p app/code/Rvvup/PaymentsHyvaCheckout/src

elif [ -n "${RVVUP_HYVA_CHECKOUT_PATH:-}" ]; then
    # Install the module from the checked out source mounted into the container, so the
    # exact code under review is tested regardless of which repository runs the build.
    echo "Installing module from path repository: $RVVUP_HYVA_CHECKOUT_PATH"
    git config --global --add safe.directory "$RVVUP_HYVA_CHECKOUT_PATH"
    # Copy (do not symlink) the module into vendor so its files live inside the Magento
    # root. A symlink pointing at the mounted source is outside the base directory and
    # Magento's template path validator refuses to render templates from there.
    composer config repositories.rvvup-hyva-checkout "{\"type\":\"path\",\"url\":\"$RVVUP_HYVA_CHECKOUT_PATH\",\"options\":{\"symlink\":false}}"
    composer require rvvup/module-magento-payments-hyva-checkout:@dev

else
    # Run the command for other values
    echo "Running setup for version: $RVVUP_HYVA_CHECKOUT_VERSION"
    composer require rvvup/module-magento-payments-hyva-checkout:$RVVUP_HYVA_CHECKOUT_VERSION
fi
