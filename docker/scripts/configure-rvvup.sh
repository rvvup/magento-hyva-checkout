echo "Running configure-rvvup.sh"

cd /bitnami/magento
#composer require rvvup/module-magento-payments-hyva-checkout:$RVVUP_HYVA_CHECKOUT_VERSION

rm -rf generated/
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush

bin/magento config:set payment/rvvup/jwt $RVVUP_API_KEY
bin/magento config:set payment/rvvup/active 1
