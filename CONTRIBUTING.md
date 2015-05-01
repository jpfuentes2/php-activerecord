# Contributing to PHP ActiveRecord #

We always appreciate contributions to PHP ActiveRecord, but we are not always able to respond as quickly as we would like.
Please do not take delays personal and feel free to remind us by commenting on issues.

### Testing ###

PHP ActiveRecord has a full set of unit tests, which are run by PHPUnit.

In order to run these unit tests, you need to install the required packages using [Composer](https://getcomposer.org/):

```sh
composer install
```

Setup a local database called "test". You'll need to ensure the credentials are correct in the connection strings in `test/helpers/config.php`

After that you can run the tests by invoking the local PHPUnit

To run all test simply use:

```sh
vendor/bin/phpunit
```

Or run a single test file by specifying its path:

```sh
vendor/bin/phpunit test/InflectorTest.php
```

Or run a single test in a file by specifying the method with the [filter option](https://phpunit.de/manual/current/en/textui.html#textui.examples.filter-patterns):

```sh
vendor/bin/phpunit --filter underscorify test/InflectorTest.php
```
