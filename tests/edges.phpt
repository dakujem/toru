<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Exceptions\BadMethodCallException;
use Dakujem\Toru\Tofu;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

//
// Developer note:
//
// These tests only check for implementation-specific cases.
// The specific behaviour should not be relied upon outside Toru library.
//

(function () {
    $mms = [
        'foo' => 'Invalid call to `Dakujem\Toru\Tofu::foo`.',

        'values' => 'Invalid call to `Dakujem\Toru\Tofu::values`. Did you mean `Dakujem\Toru\Tofu::valuesOnly`?',
        'keys' => 'Invalid call to `Dakujem\Toru\Tofu::keys`. Did you mean `Dakujem\Toru\Tofu::keysOnly`?',
        'find' => 'Invalid call to `Dakujem\Toru\Tofu::find`. Did you mean `Dakujem\Toru\Tofu::search`?',
        'findOrFail' => 'Invalid call to `Dakujem\Toru\Tofu::findOrFail`. Did you mean `Dakujem\Toru\Tofu::searchOrFail`?',
        'findOrDefault' => 'Invalid call to `Dakujem\Toru\Tofu::findOrDefault`. Did you mean `Dakujem\Toru\Tofu::search`?',

        'make' => 'Invalid call to `Dakujem\Toru\Tofu::make`. The method is not supported in partially applied form.',
        'produce' => 'Invalid call to `Dakujem\Toru\Tofu::produce`. The method is not supported in partially applied form.',
    ];
    foreach ($mms as $method => $message) {
        Assert::exception(
            function () use ($method) {
                Tofu::{$method}();
            },
            BadMethodCallException::class,
            $message,
        );
    }
})();

(function () {
    $mms = [
        'foo' => 'Invalid call to `Dakujem\Toru\Dash::foo`. To include custom decorators in the chain, `Dakujem\Toru\Dash::alter()` or `Dakujem\Toru\Dash::aggregate()` may be used.',

        'values' => 'Invalid call to `Dakujem\Toru\Dash::values`. Did you mean `Dakujem\Toru\Dash::valuesOnly`?',
        'keys' => 'Invalid call to `Dakujem\Toru\Dash::keys`. Did you mean `Dakujem\Toru\Dash::keysOnly`?',
        'find' => 'Invalid call to `Dakujem\Toru\Dash::find`. Did you mean `Dakujem\Toru\Dash::search`?',
        'findOrFail' => 'Invalid call to `Dakujem\Toru\Dash::findOrFail`. Did you mean `Dakujem\Toru\Dash::searchOrFail`?',
        'findOrDefault' => 'Invalid call to `Dakujem\Toru\Dash::findOrDefault`. Did you mean `Dakujem\Toru\Dash::search`?',

        'make' => 'Invalid call to `Dakujem\Toru\Dash::make`. The method is not supported by the `Dakujem\Toru\Dash` wrapper. Instead, call the static `Dakujem\Toru\Itera::make()` method, then wrap the result.',
        'produce' => 'Invalid call to `Dakujem\Toru\Dash::produce`. The method is not supported by the `Dakujem\Toru\Dash` wrapper. Instead, call the static `Dakujem\Toru\Itera::produce()` method, then wrap the result.',
    ];
    foreach ($mms as $method => $message) {
        Assert::exception(
            function () use ($method) {
                Dash::collect([])->{$method}();
            },
            BadMethodCallException::class,
            $message,
        );
    }
})();

(function () {
    // This test only checks for an implementation-specific case, the behaviour should not be relied upon outside Toru library.
    $foo = Dash::collect([]);
    Assert::same($foo, $foo->ensureTraversable());
})();

