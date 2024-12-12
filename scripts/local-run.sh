#!/bin/bash
set -x
set -e
BASE_DIR=$(dirname "$(realpath "$0")")
HOST="local.dev.rvvuptech.com:89"
CURRENT_DIR_NAME=$(basename "$PWD")
RVVUP_HYVA_CHECKOUT_VERSION='local'
# Ideally, this commit is pushed to docker hub and we don't rebuild everytime, but for now we rebuild temporarily.
docker compose down -v
docker image rm $CURRENT_DIR_NAME-magento:latest
docker compose up -d
$BASE_DIR/helpers/wait-for-server-startup.sh

echo "Commiting base image"
docker commit $CURRENT_DIR_NAME-magento-1 magento-hyva-store:latest
echo "Restarting server with volume attached"
docker compose -f docker-compose.local.yml up -d

$BASE_DIR/helpers/wait-for-server-startup.sh
echo -e "\033[32mSuccessfully started up server on http://$HOST\033[0m"
open http://$HOST
