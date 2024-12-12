echo "Running configure-rvvup.sh"

cd /bitnami/magento
rm -rf generated/

if [ "$RVVUP_HYVA_CHECKOUT_VERSION" == "local" ]; then
    # Run the command for "local"
    echo "Running local version setup..."
#    composer config allow-plugins.wikimedia/composer-merge-plugin true
#    composer require wikimedia/composer-merge-plugin
#    jq '.extra."merge-plugin"."include" += ["app/code/Rvvup/composer.json"]' composer.json > composer-temp.json && mv composer-temp.json composer.json
#    jq '.extra."merge-plugin"."merge-dev" = false' composer.json > composer-temp.json && mv composer-temp.json composer.json
#    composer update -W
    composer require rvvup/module-magento-payments:1.6.0 hyva-themes/magento2-compat-module-fallback:^1.0 hyva-themes/magento2-hyva-checkout:^1.1 magewirephp/magewire:*
    mkdir -p app/code/Rvvup/PaymentsHyvaCheckout

else
    # Run the command for other values
    echo "Running setup for version: $RVVUP_HYVA_CHECKOUT_VERSION"
    composer require rvvup/module-magento-payments-hyva-checkout:$RVVUP_HYVA_CHECKOUT_VERSION
fi
