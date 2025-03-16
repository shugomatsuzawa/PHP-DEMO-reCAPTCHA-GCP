FROM php:8.2-apache

RUN set -eux; apt-get update; apt-get install -y libzip-dev zlib1g-dev; docker-php-ext-install zip
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Google Cloud CLI
RUN apt-get install -y apt-transport-https ca-certificates gnupg curl
RUN echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] https://packages.cloud.google.com/apt cloud-sdk main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list && curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | gpg --dearmor -o /usr/share/keyrings/cloud.google.gpg && apt-get update -y && apt-get install google-cloud-cli -y

# NOTE: デフォルトの構成ディレクトリ /root/.config/gcloud にPHPから参照できないため、パスを変更
# https://cloud.google.com/sdk/docs/configurations?hl=ja
ENV CLOUDSDK_CONFIG=/var/www/.config/gcloud
ENV GOOGLE_APPLICATION_CREDENTIALS=/var/www/.config/gcloud/application_default_credentials.json
