echo "Running Rebuild Magento"
cd /bitnami/magento/
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
/rvvup/scripts/fix-perms.sh;
