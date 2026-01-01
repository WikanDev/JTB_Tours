#!/bin/bash
set -e

# JTB Tours Server Setup Script

echo "🚀 Starting Server Setup..."

# Update system
echo "📦 Updating system packages..."
apt-get update
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y

# Install common tools
echo "📦 Installing common tools..."
apt-get install -y git curl zip unzip software-properties-common

# Install PHP 8.2 and extensions
echo "📦 Installing PHP 8.2..."
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl

# Install Nginx
echo "📦 Installing Nginx..."
apt-get install -y nginx

# Install MySQL
echo "📦 Installing MySQL..."
apt-get install -y mysql-server

# Install Composer
echo "📦 Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js (v20)
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# Install Certbot
echo "📦 Installing Certbot..."
apt-get install -y certbot python3-certbot-nginx

# Start and Enable Services
echo "🔄 Starting services..."
systemctl enable nginx
systemctl start nginx
systemctl enable mysql
systemctl start mysql
systemctl enable php8.2-fpm
systemctl start php8.2-fpm

# Configure MySQL
echo "🗄️ Setting up Database..."
# We assume setup_database.sql is in the same directory
if [ -f "setup_database.sql" ]; then
    mysql < setup_database.sql
    echo "✅ Database configured."
else
    echo "⚠️ setup_database.sql not found, skipping DB setup."
fi

# Configure Nginx
echo "🌐 Configuring Nginx..."
if [ -f "nginx-config" ]; then
    cp nginx-config /etc/nginx/sites-available/jtb-tours
    ln -sf /etc/nginx/sites-available/jtb-tours /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    nginx -t && systemctl reload nginx
    echo "✅ Nginx configured."
else
    echo "⚠️ nginx-config not found, skipping Nginx setup."
fi

# Setup Directory Structure
echo "VE Creating directory structure..."
mkdir -p /var/www/jtb-tours/releases
mkdir -p /var/www/jtb-tours/shared/storage
mkdir -p /var/www/jtb-tours/source

# Setup Shared Env
if [ -f "env.production" ]; then
    cp env.production /var/www/jtb-tours/shared/.env
    echo "✅ Environment file installed."
fi

echo "✅ Server setup completed successfully!"
