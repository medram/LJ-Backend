FROM php:8.2-apache

# in MB
ENV MAX_FILE_SIZE=50

WORKDIR /var/www/html


RUN apt-get update && apt-get upgrade -y \
	&& apt-get install -y libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libmcrypt-dev \
        libicu-dev \
	    libxml2-dev \
	    iputils-ping \
	    curl \
	    wget \
	    unzip \
	&& apt-get clean && rm -fr /var/lib/apt/lists/*

RUN pecl install mcrypt \
	&& docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql mbstring gd xml \
	&& docker-php-ext-enable mysqli pdo pdo_mysql mbstring mcrypt xml gd \
	&& a2enmod rewrite

# Override some php.ini configs
RUN echo -e "\n\
post_max_size = ${MAX_FILE_SIZE}M \n\
upload_max_filesize = ${MAX_FILE_SIZE}M \n\
memory_limit = ${MAX_FILE_SIZE}M \n\
" > /usr/local/etc/php/conf.d/override.ini

COPY --chown=www-data:www-data . .

COPY --chown=www-data:www-data .env.prod .env

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installing PHP dependencies.
RUN composer update -n --no-cache --no-scripts && composer install -n --no-cache --no-scripts


# Fixing files and folders permissions
RUN find . -type d -exec chmod 755 {} \; \
	&& find . -type f -exec chmod 644 {} \;

USER www-data

# No need for this as a volume
# VOLUME /var/www/html

EXPOSE 80
