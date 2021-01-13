#!/bin/sh

PLUGIN_SLUG="charitable-mollie"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"
VERSION="$1";

echo "Generating build directory..."
rm -rf "$BUILD_PATH"
mkdir -p "$DEST_PATH"

echo "Installing PHP and JS dependencies..."
npm install
composer install || exit "$?"
echo "Running JS Build..."
npm run build:core || exit "$?"
echo "Cleaning up PHP dependencies..."
composer install --no-dev || exit "$?"

echo "Syncing files..."
rsync -rc --exclude-from="$PROJECT_PATH/.distignore" "$PROJECT_PATH/" "$DEST_PATH/" --delete --delete-excluded

echo "Generating zip file..."
cd "$BUILD_PATH" || exit
zip -q -r "${PLUGIN_SLUG}-${VERSION}.zip" "$PLUGIN_SLUG/"

echo "${PLUGIN_SLUG}-${VERSION}.zip file generated!"

echo "Build done!"