FROM debian:bullseye-slim

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
        ssh-client \
        sudo \
        unzip \
        vim \
        wget && \
    apt-get clean && apt-get --yes --quiet autoremove --purge && \
    rm -rf /var/lib/apt/lists/*

RUN wget -O sury.gpg https://packages.sury.org/php/apt.gpg && apt-key add sury.gpg && rm sury.gpg && \
    echo "deb https://packages.sury.org/php/ bullseye main" > /etc/apt/sources.list.d/sury.list && \
    apt-get update && \
    apt-get --no-install-suggests --yes --quiet install \
        php7.4-apcu \
        php7.4-cli \
        php7.4-curl \
        php7.4-fpm \
        php7.4-json \
        php7.4-mbstring \
        php7.4-mysql \
        php7.4-pdo \
        php7.4-xdebug \
        php7.4-xml \
        php7.4-zip && \
    apt-get clean && apt-get --yes --quiet autoremove --purge && \
    rm -rf /var/lib/apt/lists/* && \
    ln -s /usr/sbin/php-fpm7.4 /usr/local/sbin/php-fpm && \
    usermod --uid 1000 www-data && groupmod --gid 1000 www-data && \
    mkdir /srv/app && \
    usermod -d /srv/app www-data && \
    mkdir -p /run/php

COPY docker/slub-php.ini /etc/php/7.4/mods-available/slub.ini
COPY docker/slub-fpm.conf /etc/php/7.4/fpm/pool.d/zzz-slub.conf
RUN phpenmod slub

COPY --from=composer:2.0 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN mkdir -p /var/www/.composer && chown www-data:www-data /var/www/.composer
