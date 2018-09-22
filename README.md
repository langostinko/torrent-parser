Install
Your composer.json should look like:
{
    "require": {
        "katzgrau/klogger": "dev-master"
    },
    "require-dev": {
        "phpunit/phpunit": "^7"
    }
}

Tests
php7.2 bin/vendor/bin/phpunit --bootstrap bin/vendor/autoload.php all_tests.php
