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

retry_with_backoff() {
    max_attempts=5
    attempt=1
    delay=10
    while true; do
        if "$@"; then
            return 0
        fi
        if [ "$attempt" -ge "$max_attempts" ]; then
            echo "configure-hyva.sh: '$*' failed after ${attempt} attempts, giving up" >&2
            return 1
        fi
        echo "configure-hyva.sh: '$*' failed (attempt ${attempt}/${max_attempts}), retrying in ${delay}s" >&2
        sleep "$delay"
        attempt=$((attempt + 1))
        delay=$((delay * 2))
    done
}

if [ "$HYVA_CHECKOUT_VERSION" == "latest" ] || [ -z "$HYVA_CHECKOUT_VERSION" ]; then
    retry_with_backoff composer require --prefer-source hyva-themes/magento2-default-theme hyva-themes/magento2-hyva-checkout || exit 1
else
    retry_with_backoff composer require --prefer-source hyva-themes/magento2-default-theme hyva-themes/magento2-hyva-checkout:${HYVA_CHECKOUT_VERSION} || exit 1
fi
bin/magento setup:upgrade

bin/magento config:set dev/template/minify_html 0
php -r '
$c = require "app/etc/env.php";
$d = $c["db"]["connection"]["default"];
$p = new PDO("mysql:host=".$d["host"].";dbname=".$d["dbname"], $d["username"], $d["password"] ?? "");
$themeId = $p->query("SELECT theme_id FROM theme WHERE theme_path=\"Hyva/default\"")->fetchColumn();
if ($themeId === false || $themeId === null || $themeId === "") {
    fwrite(STDERR, "configure-hyva.sh: Hyva theme (Hyva/default) not found, the theme failed to install. Aborting." . PHP_EOL);
    exit(1);
}
$stmt = $p->prepare("INSERT INTO core_config_data (scope, scope_id, path, value) VALUES (\"stores\", 1, ?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
$stmt->execute(["design/theme/theme_id", $themeId]);
$stmt->execute(["hyva_themes_checkout/general/checkout", "default"]);
echo "Hyva theme ID set to: " . $themeId . PHP_EOL;
' || exit 1
