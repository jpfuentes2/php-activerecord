# Setup #

1. Setup a local database called "test"
1. Ensure the credentials are correct in the connection strings in test/helpers/config.php
1. Install [composer](https://getcomposer.org/)
1. `cd /path/to/php-activerecord`
1. `composer install`

# Running tests #
1. `vendor/bin/phpunit` will run all tests
1. `vendor/bin/phpunit test/InflectorTest.php` will run the specified test
