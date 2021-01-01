ARG BASE_IMAGE
FROM ${BASE_IMAGE} AS sources

COPY --from=composer:2.0.7 /usr/bin/composer /usr/bin/
COPY . /application

WORKDIR "/application"
COPY .env .env.local

RUN composer install --no-interaction

FROM ${BASE_IMAGE}
WORKDIR "/application"
COPY --from=sources /application/ /application/
