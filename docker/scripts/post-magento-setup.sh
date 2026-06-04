echo "Running post-magento-setup.sh"

cd /bitnami/magento

bin/magento hyva:config:generate
npm --prefix vendor/hyva-themes/magento2-default-theme/web/tailwind/ ci
npm --prefix vendor/hyva-themes/magento2-default-theme/web/tailwind/ run build-prod

/rvvup/scripts/rebuild-magento.sh

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

bin/magento config:set payment/rvvup/jwt $RVVUP_API_KEY
bin/magento config:set payment/rvvup/active 1
