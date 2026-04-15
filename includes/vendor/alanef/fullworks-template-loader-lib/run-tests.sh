#!/bin/bash

# Make sure composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install
fi

# Run the tests
./vendor/bin/phpunit