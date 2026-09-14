<?php

// Public test dependencies are isolated from the application's private bundles.
require getenv('MAIL_BUNDLE_TEST_AUTOLOAD') ?: __DIR__.'/vendor/autoload.php';
require __DIR__.'/WebeakTestDoubles.php';
spl_autoload_register(static function ($class) {
    $prefix = 'Webeak\\Bundle\\MailBundle\\';
    if (strpos($class, $prefix) === 0) {
        $root = getenv('MAIL_BUNDLE_TEST_SOURCE') ?: dirname(__DIR__).'/src';
        require $root.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }
});
