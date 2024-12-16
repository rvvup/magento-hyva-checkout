ARG MAGENTO_VERSION=2
FROM docker.io/bitnami/magento:${MAGENTO_VERSION}
COPY ./docker/scripts /rvvup/scripts
RUN apt-get update &&  apt-get install -y \
    unzip \
    git \
    jq \
    vim \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs

ENTRYPOINT ["/rvvup/scripts/entrypoint.sh"]
