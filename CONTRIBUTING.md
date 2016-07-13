# Contributing to PHP ActiveRecord #

We always appreciate contributions to PHP ActiveRecord, but we are not always able to respond as quickly as we would like.
Please do not take delays personal and feel free to remind us by commenting on issues.

### Testing ###

PHP ActiveRecord has a full set of unit tests, which are run by PHPUnit.

In order to run these unit tests, you need to install the required packages using [Composer](https://getcomposer.org/):

```sh
composer install
```

After that you can run the tests by invoking the local PHPUnit

To run all test simply use:

```sh
vendor/bin/phpunit
```

Or run a single test file by specifying its path:

```sh
vendor/bin/phpunit test/InflectorTest.php
```

#### Skipped Tests ####

You might notice that some tests are marked as skipped. To obtain more information about skipped
tests, pass the `--verbose` flag to PHPUnit:

```sh
vendor/bin/phpunit --verbose
```

Some common steps for fixing skipped tests are to:

* Install `memcached` and the PHP memcached extension (e.g., `brew install php56-memcache memcached` on macOS)
* Install the PDO drivers for PostgreSQL (e.g., `brew install php56-pdo-pgsql` on macOS)
* Create a MySQL database and a PostgreSQL database. You can either create these such that they are available at the default locations of `mysql://test:test@127.0.0.1/test` and `pgsql://test:test@127.0.0.1/test` respectively. Alternatively, you can set the `PHPAR_MYSQL` and `PHPAR_PGSQL` environment variables to specify a different location for the MySQL and PostgreSQL databases.
