#!/bin/bash
start=$(date +%s)
docker compose down -v
HOST="local.dev.rvvuptech.com:89"
# Ideally, this commit is pushed to docker hub and we don't rebuild everytime, but for now we rebuild temporarily.
docker image rm magento-hyva-checkout-magento:latest
docker compose up -d
attempt=1
while true; do
    http_status=$(curl -o /dev/null -s -w "%{http_code}\n" -I "http://${HOST}/magento_version")

    if [ "$http_status" -eq 200 ]; then
        echo -e "\rServer responded with 200 OK / Time taken: $(($(date +%s) - start)) seconds, continuing..."
        break
    else
        echo -ne "\rAttempt $attempt: Waiting for server to be up (Might take a couple of minutes). Current status code: $http_status / Time taken: $(($(date +%s) - start)) seconds"
        attempt=$((attempt + 1))
        sleep 2
    fi
done

echo "Commiting base image"
docker commit magento-hyva-checkout-magento-1 magento-hyva-store:latest
echo "Restarting server with volume attached"
docker compose -f local.docker-compose.yml up -d

attempt=1
while true; do
    http_status=$(curl -o /dev/null -s -w "%{http_code}\n" -I "http://${HOST}/magento_version")

    if [ "$http_status" -eq 200 ]; then
        echo -e "\rServer responded with 200 OK / Time taken: $(($(date +%s) - start)) seconds, continuing..."
        break
    else
        echo -ne "\rAttempt $attempt: Waiting for server to be up (Might take a couple of minutes). Current status code: $http_status / Time taken: $(($(date +%s) - start)) seconds"
        attempt=$((attempt + 1))
        sleep 2
    fi
done