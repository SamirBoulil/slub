FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN echo 'APT::Install-Recommends "0" ; APT::Install-Suggests "0" ;' > /etc/apt/apt.conf.d/01-no-recommended && \
    echo 'path-exclude=/usr/share/man/*' > /etc/dpkg/dpkg.cfg.d/path_exclusions && \
    echo 'path-exclude=/usr/share/doc/*' >> /etc/dpkg/dpkg.cfg.d/path_exclusions && \
    apt-get update && \
    apt-get --no-install-recommends --no-install-suggests --yes --quiet install \
        apt-transport-https \
        bash-completion \
        ca-certificates \
        curl \
        git \
        gnupg \
        make \
        ssh-client \
        sudo \
        unzip \
        vim \
        wget && \
    apt-get clean && apt-get --yes --quiet autoremove --purge && \
    rm -rf /var/lib/apt/lists/*

RUN apt-get update && \
    apt-get --no-install-suggests --yes --quiet install \
        php-apcu \
        php-cli \
        php-curl \
        php-fpm \
        php-json \
        php-mbstring \
        php-mysql \
        php-pdo \
        php-xdebug \
        php-xml \
        php-zip && \
    apt-get clean && apt-get --yes --quiet autoremove --purge && \
    rm -rf /var/lib/apt/lists/* && \
    usermod --uid 1000 www-data && groupmod --gid 1000 www-data && \
    mkdir /srv/app && \
    usermod -d /srv/app www-data && \
    mkdir -p /run/php

COPY docker/slub-php.ini /etc/php/8.2/mods-available/slub.ini
COPY docker/slub-fpm.conf /etc/php/8.2/fpm/pool.d/zzz-slub.conf
RUN phpenmod slub

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN mkdir -p /var/www/.composer && chown www-data:www-data /var/www/.composer
