#!/bin/bash

# SNIJ Drupal Setup Script
# Run this after initial deployment to configure Drupal

echo "=== SNIJ Drupal Setup ==="
echo ""

# Wait for database to be ready
echo "Waiting for database..."
sleep 5

# Install Drupal if not installed
if [ ! -f "web/sites/default/settings.php" ]; then
    echo "Installing Drupal..."
    drush site:install minimal \
        --db-url="$DATABASE_URL" \
        --site-name="SNIJ - البوابة الوطنية للمعلومات القانونية" \
        --account-name=admin \
        --account-pass=admin \
        --locale=ar \
        -y
fi

echo "Enabling required modules..."

# Enable core modules
drush en -y \
    jsonapi \
    content_translation \
    language \
    locale \
    datetime \
    text \
    options \
    taxonomy \
    node

# Enable contrib modules
drush en -y \
    admin_toolbar \
    admin_toolbar_tools \
    jsonapi_extras \
    pathauto \
    metatag

echo "Importing configuration..."
drush config:import -y || true

echo "Adding languages..."
drush language-add ar || true
drush language-add fr || true
drush language-add en || true

echo "Setting Arabic as default language..."
drush config:set system.site default_langcode ar -y

echo "Creating content types if needed..."
# This will be handled by config import

echo "Running content seed script..."
drush php:script scripts/seed-content.php

echo "Clearing caches..."
drush cr

echo ""
echo "=== Setup Complete ==="
echo "Admin URL: /user/login"
echo "API URL: /api"
echo ""
