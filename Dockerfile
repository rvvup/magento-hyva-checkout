ARG MAGENTO_VERSION=2
FROM docker.io/bitnamilegacy/magento-archived:${MAGENTO_VERSION}
ARG CERTS_TO_LOAD=""
COPY ./docker/scripts /rvvup/scripts
RUN apt-get update &&  apt-get install -y \
    unzip \
    git \
    jq \
    vim \
    curl \
    openssl \
    && rm -rf /var/lib/apt/lists/*

RUN CERTS_TO_LOAD="${CERTS_TO_LOAD}" /rvvup/scripts/setup-certs.sh && update-ca-certificates

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs

ENTRYPOINT ["/rvvup/scripts/entrypoint.sh"]
