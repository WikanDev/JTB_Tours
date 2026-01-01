#!/bin/bash

# JTB Tours Deployment Script
# This script automates the deployment process

set -e  # Exit on error

echo "🚀 Starting JTB Tours Deployment..."

# Define variables
RELEASE_DATE=$(date +%Y%m%d%H%M%S)
APP_DIR="/var/www/jtb-tours"
RELEASE_DIR="$APP_DIR/releases/$RELEASE_DATE"
CURRENT_DIR="$APP_DIR/current"
SHARED_DIR="$APP_DIR/shared"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}📁 Creating release directory...${NC}"
mkdir -p $RELEASE_DIR

echo -e "${YELLOW}📥 Copying application files...${NC}"
cd $APP_DIR
cp -r source/* $RELEASE_DIR/

echo -e "${YELLOW}🔗 Creating symbolic links to shared resources...${NC}"
rm -rf $RELEASE_DIR/storage
ln -s $SHARED_DIR/storage $RELEASE_DIR/storage
ln -s $SHARED_DIR/.env $RELEASE_DIR/.env

echo -e "${YELLOW}📦 Installing Composer dependencies...${NC}"
cd $RELEASE_DIR
composer install --no-dev --optimize-autoloader --no-interaction

echo -e "${YELLOW}📦 Installing NPM dependencies...${NC}"
npm ci --prefer-offline --no-audit

echo -e "${YELLOW}🏗️  Building frontend assets...${NC}"
npm run build

echo -e "${YELLOW}🔄 Running database migrations...${NC}"
php artisan migrate --force

echo -e "${YELLOW}🧹 Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo -e "${YELLOW}🔗 Updating current symlink...${NC}"
rm -f $CURRENT_DIR
ln -s $RELEASE_DIR $CURRENT_DIR

echo -e "${YELLOW}🔒 Setting permissions...${NC}"
chown -R www-data:www-data $APP_DIR
chmod -R 755 $RELEASE_DIR
chmod -R 775 $SHARED_DIR/storage
chmod -R 775 $RELEASE_DIR/bootstrap/cache

echo -e "${YELLOW}🔄 Reloading services...${NC}"
systemctl reload php8.2-fpm
systemctl reload nginx

echo -e "${YELLOW}🧹 Cleaning old releases (keeping last 3)...${NC}"
cd $APP_DIR/releases
ls -t | tail -n +4 | xargs -r rm -rf

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${GREEN}🌐 Application is now live at: https://jtb-tours.wikandev.com${NC}"
