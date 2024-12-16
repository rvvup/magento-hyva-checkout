echo "Running setup.sh"
/rvvup/scripts/configure-base-store.sh;
/rvvup/scripts/configure-hyva.sh;
/rvvup/scripts/configure-rvvup.sh;
/rvvup/scripts/post-magento-setup.sh;

if [ "$RVVUP_HYVA_CHECKOUT_VERSION" == "local" ]; then
  cd /bitnami/magento
  # Only run in first attempt, then reset
  echo "echo \"Ignored running base store config\"" > /rvvup/scripts/configure-base-store.sh
  echo "echo \"Ignored running hyva setup\"" > /rvvup/scripts/configure-hyva.sh
  echo "echo \"Ignored running  rvvup config\"" > /rvvup/scripts/configure-rvvup.sh
  sed -i '1s/^/RVVUP_HYVA_CHECKOUT_VERSION=local \n/' /rvvup/scripts/fix-perms.sh
  echo "/rvvup/scripts/run-on-local-volume.sh" > /rvvup/scripts/post-magento-setup.sh
fi
/rvvup/scripts/fix-perms.sh;
/opt/bitnami/scripts/magento/run.sh;
