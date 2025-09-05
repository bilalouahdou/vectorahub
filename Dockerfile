# Multi-stage build for smaller final image
FROM composer:latest AS composer-stage
FROM php:8.2-apache

# Install system dependencies in a single layer with cleanup
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    postgresql-client \
    python3 \
    python3-pip \
    python3-venv \
    zip \
    unzip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Set working directory
WORKDIR /var/www/html

# Copy composer from official image
COPY --from=composer-stage /usr/bin/composer /usr/bin/composer

# Copy only essential files first (for better caching)
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-scripts && composer clear-cache; fi

# Copy requirements.txt for Python dependencies
COPY requirements.txt* ./

# Create Python virtual environment and install dependencies
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --upgrade pip \
    && if [ -f requirements.txt ]; then /opt/venv/bin/pip install --no-cache-dir -r requirements.txt; fi \
    && rm -rf ~/.cache/pip

# Set Python environment
ENV PATH="/opt/venv/bin:$PATH"
ENV PYTHONPATH="/var/www/html"

# Copy application files (excluding large unnecessary files)
COPY --chown=www-data:www-data . .

# Remove unnecessary files and create directories with proper permissions
RUN rm -rf \
    .git \
    .gitignore \
    README.md \
    *.md \
    deployment/README.md \
    docs \
    __pycache__ \
    .pytest_cache \
    *.pyc \
    *.pyo \
    && mkdir -p uploads outputs temp logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 uploads outputs temp logs \
    && find . -name "*.log" -delete \
    && find . -name ".DS_Store" -delete

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && if [ -f deployment/apache-config.conf ]; then cp deployment/apache-config.conf /etc/apache2/sites-available/000-default.conf; fi

# Add memory optimization
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]