echo "Running post-magento-setup.sh"

cd /bitnami/magento

bin/magento hyva:config:generate
npm --prefix vendor/hyva-themes/magento2-default-theme/web/tailwind/ ci
npm --prefix vendor/hyva-themes/magento2-default-theme/web/tailwind/ run build-prod
bin/magento setup:upgrade
