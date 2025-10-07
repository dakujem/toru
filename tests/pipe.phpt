<?php

declare(strict_types=1);

use Tester\Environment;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

if (PHP_VERSION_ID < 80500) {
    Environment::skip('This test requires PHP 8.5 or higher');
}

(function () {
    // Must be in a separate file, otherwise this test would cause parse error with versions prior to PHP 8.5
    require __DIR__ . '/pipe_internals.php';
})();
