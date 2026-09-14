# Queue publication regression tests

Run with PHP 8.2, PDO SQLite and Composer:

```sh
composer --working-dir=Tests install --no-scripts --no-plugins
Tests/vendor/bin/phpunit -c Tests/phpunit.xml
```

The public test dependencies are isolated in `Tests/vendor`; they do not change
this bundle's production requirements. They intentionally match the legacy
Symfony 4 / SwiftMailer APIs. Use this harness only in an isolated test environment.
`WebeakTestDoubles.php` replaces peripheral private dependencies (logging, array
helpers and the basic entity superclass). The actual mail bundle, Doctrine
ORM/DBAL, Symfony event dispatcher and Swift message serialization are exercised.
The SMTP endpoint and task launcher are doubles: no real mail is sent.

The publication test uses a SQLite file and separate producer, consumer and
observer connections. It checks that the tracking row is committed while only a
complete temporary file exists, then immediately consumes the published message
and verifies that a late producer flush does not overwrite the recorded success.
Additional cases cover SQL failure, open transactions, write/rename failure,
partial batch success, SMTP retry/abandon, caller-managed tracking and untracked
messages. Deprecation notices from legacy dependencies under PHP 8.2 are excluded;
other PHP errors remain enabled.

For an existing isolated dependency installation, set `MAIL_BUNDLE_TEST_AUTOLOAD`
to its `vendor/autoload.php`. `MAIL_BUNDLE_TEST_SOURCE` can point to another `src`
directory to run the same regression against an unpatched checkout.
