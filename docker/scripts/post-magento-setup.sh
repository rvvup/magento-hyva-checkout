echo "Running post-magento-setup.sh"

cd /bitnami/magento

bin/magento hyva:config:generate
npm --prefix vendor/hyva-themes/magento2-default-theme/web/tailwind/ ci
npm --prefix vendor/hyva-themes/magento2-default-theme/web/tailwind/ run build-prod

/rvvup/scripts/rebuild-magento.sh

vendor/bin/n98-magerun2 config:store:set design/theme/theme_id 5 --scope=stores --scope-id=1
vendor/bin/n98-magerun2 config:store:set hyva_themes_checkout/general/checkout default

bin/magento config:set payment/rvvup/jwt $RVVUP_API_KEY
bin/magento config:set payment/rvvup/active 1
