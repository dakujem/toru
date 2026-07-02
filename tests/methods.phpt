<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Itera;
use Dakujem\Toru\IteraFn;
use Dakujem\Toru\Tofu;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

/**
 * In this test we verify that all methods available via the `Itera` class are implemented
 * as real (non-magic) methods on the `Dash` and `Tofu`/`IteraFn` wrappers, with a couple of exceptions.
 */
(function () {
    $iteraRef = new ReflectionClass(Itera::class);
    /** @var string[] $requiredMethods */
    $requiredMethods = Itera::toArray(Itera::apply(
        Itera::filter(
            $iteraRef->getMethods(),
            fn(ReflectionMethod $m) => $m->isPublic(),
        ),
        fn(ReflectionMethod $m) => $m->getName(),
    ));

    Assert::same(true, Itera::count($requiredMethods) > 10);

    // Assert that the given class implements `$name` as a real, public method with the expected static-ness.
    $assertImplemented = function (string $class, string $name, bool $static): void {
        $ref = new ReflectionClass($class);
        Assert::true($ref->hasMethod($name), "Method `$name` is NOT implemented in $class");
        $method = $ref->getMethod($name);
        Assert::true($method->isPublic(), "Method `$name` must be public in $class");
        Assert::same($static, $method->isStatic(), "Method `$name` has unexpected static-ness in $class");
    };

    // Dash exposes instance methods. `make`/`produce` are unsupported (they only produce a hint),
    // and `ensureTraversable` returns the wrapper itself rather than forwarding directly.
    $allowedExceptions = [
        'make',
        'produce',
    ];
    foreach ($requiredMethods as $m) {
        if (in_array($m, $allowedExceptions)) {
            continue;
        }
        $assertImplemented(Dash::class, $m, static: false);
    }

    // Tofu (and the deprecated IteraFn alias) expose static factory methods.
    $allowedPartiallyAppliedExceptions = [
        'make',
        'produce',
    ];
    foreach ($requiredMethods as $m) {
        if (in_array($m, $allowedPartiallyAppliedExceptions)) {
            continue;
        }
        $assertImplemented(Tofu::class, $m, static: true);
        $assertImplemented(IteraFn::class, $m, static: true);
    }
})();
