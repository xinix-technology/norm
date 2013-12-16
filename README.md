NORM is (N)ot ORM
=================

The goal of this library is to make an ORM framework as intermediate layer for
various data store, such as PDO, PHP-MongoDB, etc.

To see the API Documentation you have to generate the API Documentation first.

```

composer update
./vendor/bin/phpdoc.php -d ./src/ -t ./docs/api/

```