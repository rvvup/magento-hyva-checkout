#!/usr/bin/env bash
# We need this file and these checks because there are three different possible scenarios when we reach here:
#
#   1. We are running on a corporate laptop with SSL inspection active, in which case we need to download
#      the certificates listed in CERTS_TO_LOAD.
#   2. We are running on a corporate laptop without SSL inspection active, in which case we want to fail
#      early with a useful error message, rather than just getting SSL errors later in the build.
#   3. We are running without any requirement for extra certificates, and the CERTS_TO_LOAD variable is
#      empty or unset — in which case we skip entirely.
#
# CERTS_TO_LOAD: comma-separated list of .tar URLs to download and install into /usr/local/share/ca-certificates/
# Example: CERTS_TO_LOAD=https://example.com/corp_certs.tar,https://example.com/other_cert.tar
#
# This script aims to auto-detect which of the three scenarios we're in, and error when it makes sense to.

set -euo pipefail

if [ -z "${CERTS_TO_LOAD:-}" ]; then
  echo "NOTE: CERTS_TO_LOAD is not set, skipping certificate installation."
  exit 0
fi

if openssl s_client -connect google.com:443 </dev/null 2>/dev/null | grep -q ca.zopa.eu.goskope.com; then
  IFS=',' read -ra CERT_URLS <<< "$CERTS_TO_LOAD"
  for url in "${CERT_URLS[@]}"; do
    url="$(echo "$url" | xargs)"  # trim whitespace
    echo "Downloading certificates from: $url"
    curl -skS -L "$url" | tar xvf - -C /usr/local/share/ca-certificates/
  done
  chown root:root -R "/usr/local/share/ca-certificates/"
  echo "Downloaded certificates"

  rm -f /usr/local/share/ca-certificates/*_caCert.crt
else
  echo "NOTE: We have detected that you do not need extra SSL certificates, skipping download."
fi
