FROM php:8.2-apache

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

# Fixing apache DocumentRoot
RUN sed -i 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

COPY --chown=www-data:www-data . .

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installing PHP dependencies.
RUN composer update -n --no-cache --no-scripts && composer install -n --no-cache --no-scripts


# Fixing files and folders permissions
RUN find . -type d -exec chmod 755 {} \; \
	&& find . -type f -exec chmod 644 {} \;

USER www-data

VOLUME /var/www/html

EXPOSE 80
