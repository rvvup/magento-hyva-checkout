#!/bin/bash
set -e
BASE_DIR=$(dirname "$(realpath "$0")")
BASE_URL=http://local.dev.rvvuptech.com:89
magento_config() {
    docker compose exec -T -w /bitnami/magento magento bin/magento config:set tax/display/type "$1"
    docker compose exec -T -w /bitnami/magento magento bin/magento cache:flush
}

docker compose up -d --build
$BASE_DIR/helpers/wait-for-server-startup.sh

if [ "$1" == "--ui" ]; then
    TEST_BASE_URL=$BASE_URL npx playwright test --ui --grep-invert @excluding-tax
else
    TEST_BASE_URL=$BASE_URL npx playwright test --grep-invert @excluding-tax

    # Second pass with prices displayed excluding tax (Magento's default), restored afterwards.
    magento_config 1
    status=0
    TEST_BASE_URL=$BASE_URL npx playwright test --grep @excluding-tax || status=$?
    magento_config 2
    exit $status
fi
