echo "Running against local volume"
cd /bitnami/magento/

jq '.extra."merge-plugin"."include" += ["app/code/Rvvup/PaymentsHyvaCheckout/composer.json"]' composer.json > composer-temp.json && mv composer-temp.json composer.json
jq '.extra."merge-plugin"."merge-dev" = false' composer.json > composer-temp.json && mv composer-temp.json composer.json
composer update -W
/rvvup/scripts/rebuild-magento.sh

retry_with_backoff() {
    max_attempts=5
    attempt=1
    delay=10
    while true; do
        if "$@"; then
            return 0
        fi
        if [ "$attempt" -ge "$max_attempts" ]; then
            echo "run-on-local-volume.sh: '$*' failed after ${attempt} attempts, giving up" >&2
            return 1
        fi
        echo "run-on-local-volume.sh: '$*' failed (attempt ${attempt}/${max_attempts}), retrying in ${delay}s" >&2
        sleep "$delay"
        attempt=$((attempt + 1))
        delay=$((delay * 2))
    done
}
retry_with_backoff bin/magento config:set payment/rvvup/jwt $RVVUP_API_KEY
retry_with_backoff bin/magento config:set payment/rvvup/active 1
