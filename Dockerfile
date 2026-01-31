FROM php:8.3-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy backend files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
FROM node:18-alpine
RUN apk add --no-cache python3 make g++ vips-dev
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm install -g tsx
EXPOSE 4000
CMD ["tsx", "src/server.ts"]