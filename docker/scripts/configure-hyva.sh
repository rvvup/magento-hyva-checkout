echo "Running configure-hyva.sh"

mkdir -p /root/.ssh/ && echo "$HYVA_SSH_PRIVATE_KEY" > /root/.ssh/id_ed25519
chmod 700 /root/.ssh && chmod 600 /root/.ssh/id_ed25519
ssh-keyscan -t rsa gitlab.hyva.io >> /root/.ssh/known_hosts
cd /bitnami/magento

composer config repositories.hyva-themes/magento2-theme-module git git@gitlab.hyva.io:hyva-themes/magento2-theme-module.git
composer config repositories.hyva-themes/magento2-reset-theme git git@gitlab.hyva.io:hyva-themes/magento2-reset-theme.git
composer config repositories.hyva-themes/magento2-email-module git git@gitlab.hyva.io:hyva-themes/magento2-email-module.git
composer config repositories.hyva-themes/magento2-default-theme git git@gitlab.hyva.io:hyva-themes/magento2-default-theme.git
composer config repositories.hyva-themes/magento2-compat-module-fallback git git@gitlab.hyva.io:hyva-themes/magento2-compat-module-fallback.git
composer config repositories.hyva-themes/magento2-order-cancellation-webapi git git@gitlab.hyva.io:hyva-themes/magento2-order-cancellation-webapi.git
composer config repositories.hyva-themes/hyva-checkout git git@gitlab.hyva.io:hyva-checkout/checkout.git
if [ "$HYVA_CHECKOUT_VERSION" == "latest" ] || [ -z "$HYVA_CHECKOUT_VERSION" ]; then
    composer require --prefer-source hyva-themes/magento2-default-theme hyva-themes/magento2-hyva-checkout
else
    composer require --prefer-source hyva-themes/magento2-default-theme hyva-themes/magento2-hyva-checkout:${HYVA_CHECKOUT_VERSION}
fi
bin/magento setup:upgrade

bin/magento config:set dev/template/minify_html 0
vendor/bin/n98-magerun2 config:store:set design/theme/theme_id 5 --scope=stores --scope-id=1
vendor/bin/n98-magerun2 config:store:set hyva_themes_checkout/general/checkout default
