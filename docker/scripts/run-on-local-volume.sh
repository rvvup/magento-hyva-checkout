echo "Running run on local"
cd /bitnami/magento/
#composer config allow-plugins.wikimedia/composer-merge-plugin true
#composer require wikimedia/composer-merge-plugin
#jq '.extra."merge-plugin"."include" += ["app/code/Rvvup/composer.json"]' composer.json > composer-temp.json && mv composer-temp.json composer.json
#jq '.extra."merge-plugin"."merge-dev" = false' composer.json > composer-temp.json && mv composer-temp.json composer.json
#composer update -W
composer require rvvup/module-magento-payments:1.6.0 hyva-themes/magento2-compat-module-fallback:^1.0 hyva-themes/magento2-hyva-checkout:^1.1 magewirephp/magewire:*
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento config:set payment/rvvup/jwt $RVVUP_API_KEY
bin/magento config:set payment/rvvup/active 1
bin/magento cache:flush
/rvvup/scripts/fix-perms.sh;
