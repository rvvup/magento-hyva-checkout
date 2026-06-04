echo "Running configure-hyva.sh"

mkdir -p /root/.ssh
chmod 700 /root/.ssh
printf '%s' "$HYVA_SSH_PRIVATE_KEY" | tr -d '\r' > /root/.ssh/id_ed25519
echo >> /root/.ssh/id_ed25519
chmod 600 /root/.ssh/id_ed25519
ssh-keyscan -t rsa gitlab.hyva.io >> /root/.ssh/known_hosts
cd /bitnami/magento

composer config repositories.hyva-themes/magento2-theme-module git git@gitlab.hyva.io:hyva-themes/magento2-theme-module.git
composer config repositories.hyva-themes/magento2-reset-theme git git@gitlab.hyva.io:hyva-themes/magento2-reset-theme.git
composer config repositories.hyva-themes/magento2-email-module git git@gitlab.hyva.io:hyva-themes/magento2-email-module.git
composer config repositories.hyva-themes/magento2-default-theme git git@gitlab.hyva.io:hyva-themes/magento2-default-theme.git
composer config repositories.hyva-themes/magento2-compat-module-fallback git git@gitlab.hyva.io:hyva-themes/magento2-compat-module-fallback.git
composer config repositories.hyva-themes/magento2-order-cancellation-webapi git git@gitlab.hyva.io:hyva-themes/magento2-order-cancellation-webapi.git
composer config repositories.hyva-themes/magento2-mollie-theme-bundle git git@gitlab.hyva.io:hyva-themes/hyva-compat/magento2-mollie-theme-bundle.git
composer config repositories.hyva-themes/hyva-checkout git git@gitlab.hyva.io:hyva-checkout/checkout.git
if [ "$HYVA_CHECKOUT_VERSION" == "latest" ] || [ -z "$HYVA_CHECKOUT_VERSION" ]; then
    composer require --prefer-source hyva-themes/magento2-default-theme hyva-themes/magento2-hyva-checkout
else
    composer require --prefer-source hyva-themes/magento2-default-theme hyva-themes/magento2-hyva-checkout:${HYVA_CHECKOUT_VERSION}
fi
bin/magento setup:upgrade

bin/magento config:set dev/template/minify_html 0
php -r '
$c = require "app/etc/env.php";
$d = $c["db"]["connection"]["default"];
$p = new PDO("mysql:host=".$d["host"].";dbname=".$d["dbname"], $d["username"], $d["password"] ?? "");
$themeId = $p->query("SELECT theme_id FROM theme WHERE theme_path=\"Hyva/default\"")->fetchColumn();
$stmt = $p->prepare("INSERT INTO core_config_data (scope, scope_id, path, value) VALUES (\"stores\", 1, ?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
$stmt->execute(["design/theme/theme_id", $themeId]);
$stmt->execute(["hyva_themes_checkout/general/checkout", "default"]);
echo "Hyva theme ID set to: " . $themeId . PHP_EOL;
'
