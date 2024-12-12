echo "Running setup.sh"
/rvvup/scripts/configure-base-store.sh;
/rvvup/scripts/configure-hyva.sh;
/rvvup/scripts/configure-rvvup.sh;
/rvvup/scripts/post-magento-setup.sh;

cd /bitnami/magento
# Only run in first attempt, then reset
echo "echo \"Ignored running base store config\"" > /rvvup/scripts/configure-base-store.sh
echo "echo \"Ignored running hyva setup\"" > /rvvup/scripts/configure-hyva.sh
echo "echo \"Ignored running  rvvup config\"" > /rvvup/scripts/configure-rvvup.sh
echo "/rvvup/scripts/run-on-local-volume.sh" > /rvvup/scripts/post-magento-setup.sh

/rvvup/scripts/fix-perms.sh;
/opt/bitnami/scripts/magento/run.sh;
