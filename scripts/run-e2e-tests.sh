#!/bin/bash
set -e
BASE_DIR=$(dirname "$(realpath "$0")")
docker compose up -d --build
$BASE_DIR/helpers/wait-for-server-startup.sh

if [ "$1" == "--ui" ]; then
    TEST_BASE_URL=http://local.dev.rvvuptech.com:89 npx playwright test --ui
else
    TEST_BASE_URL=http://local.dev.rvvuptech.com:89 npx playwright test
fi
