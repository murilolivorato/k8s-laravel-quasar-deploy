FROM php:8.3.4-fpm-alpine

# Install Nginx and dependencies
RUN apk add --no-cache nginx wget

# Create the necessary directories
RUN mkdir -p /run/nginx
RUN mkdir -p /app
WORKDIR /app

# Copy the Laravel application first (to avoid changing the working directory unnecessarily)
COPY . /app

# Copy Nginx config with root privileges
COPY nginx/default_prod.conf /etc/nginx/default_prod.conf

# Install Composer
RUN wget https://getcomposer.org/composer.phar && \
    chmod +x composer.phar && \
    mv composer.phar /usr/local/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set proper permissions for Laravel app
RUN chown -R www-data:www-data /app

# Copy the startup script and make it executable
COPY nginx/startup.sh /app/nginx/startup.sh

# Make sure the startup script is executable (using root user)
RUN chmod +x /app/nginx/startup.sh

# Expose port 80
EXPOSE 80

# Switch to www-data user for the runtime
USER www-data

# Command to start PHP-FPM and Nginx
CMD ["sh", "/app/nginx/startup.sh"]