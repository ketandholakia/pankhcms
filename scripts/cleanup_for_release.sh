#!/bin/bash
# Cleanup script for preparing project for release

set -e

# Remove runtime/generated files
rm -rf storage/backups/*
rm -rf storage/cache/*
rm -rf storage/logs/*

# Remove uploads (user content/media)
rm -rf public/uploads/media/*
rm -rf public/uploads/settings/*
rm -rf public/uploads/slider/*

# Remove local environment files
rm -f .env

# Remove debug logs
find . -type f -name '*.log' -delete

# Remove temp/cache files
find . -type f -name '*.tmp' -delete
find . -type f -name '*.cache' -delete

# Remove database files (if not needed)
find . -type f -name '*.sqlite' -delete
find . -type f -name '*.db' -delete

# Remove node_modules and dist/build (if present)
rm -rf node_modules/
rm -rf dist/
rm -rf build/
rm -rf public/build/

# Remove unnecessary scripts (edit as needed)
# rm -rf scripts/debug_page_update.php
# rm -rf database/sqlite-php-scripts/*

# Done
echo "Cleanup complete. Project is ready for release."
