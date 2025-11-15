FROM php:8.3-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Configure PHP
RUN echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.cookie_secure = 0" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/security.ini

# Create entrypoint script
RUN echo '#!/bin/bash\n\
# Fix permissions for the database directory\n\
mkdir -p /var/www/html/database\n\
chown -R www-data:www-data /var/www/html\n\
chmod -R 755 /var/www/html\n\
chmod 775 /var/www/html/database\n\
\n\
# Start PHP-FPM\n\
exec php-fpm' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html

ENTRYPOINT ["/entrypoint.sh"]
