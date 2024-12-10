echo "Running setup.sh"
/rvvup/scripts/configure-base-store.sh;
/rvvup/scripts/configure-hyva.sh;
/rvvup/scripts/configure-rvvup.sh;
/rvvup/scripts/post-magento-setup.sh;
/rvvup/scripts/fix-perms.sh;
cd /bitnami/magento
echo "echo \"Run file\"" > /rvvup/scripts/configure-base-store.sh
echo "echo \"Run file\"" > /rvvup/scripts/configure-hyva.sh
echo "echo \"Run file\"" > /rvvup/scripts/configure-rvvup.sh
echo "/rvvup/scripts/run-on-local-volume.sh" > /rvvup/scripts/post-magento-setup.sh
sed -i 's/^opcache\.enable *= *1/opcache.enable = 0/' /opt/bitnami/php/etc/php.ini
sed -i 's/^opcache\.enable_cli *= *1/opcache.enable_cli = 0/' /opt/bitnami/php/etc/php.ini
rm -rf var/cache/* var/generation/* var/page_cache/* var/view_preprocessed/* var/di/* pub/static/* generated/*

/opt/bitnami/scripts/magento/run.sh;
