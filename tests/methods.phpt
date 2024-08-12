<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Itera;
use Dakujem\Toru\IteraFn;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

/**
 * In this test we look for `@method` doc-comment definitions in Dash and IteraFn classes.
 * All methods available via `Itera` class must be callable via `Dash` or `IteraFn`, with a coule of exceptions.
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

    $extractMethods = function (string $class) {
        $docComment = (new ReflectionClass($class))->getDocComment();
        $matches = [];
        $matchDynamicMethods = '/@method\s+(static\s+)?(.+?)\s+(.+?)\s*\(\s*(.*?)\s*\)/iu';
        preg_match_all($matchDynamicMethods, $docComment, $matches, PREG_SET_ORDER);

        $dynamicMethods = [];
        foreach ($matches as $match) {
            $isStatic = !empty($match[1]);
            $returnType = $match[2];
            $methodName = $match[3];
            $parameters = $match[4];

            $dynamicMethods[$methodName] = [
                'isStatic' => $isStatic,
                'returnType' => $returnType,
                'methodName' => $methodName,
                'parameters' => $parameters,
            ];
        }
        return $dynamicMethods;
    };

    // Dash
    $dashMethods = $extractMethods(Dash::class);
    $allowedExceptions = [
        'make',
        'produce',
        'ensureTraversable',
    ];
    foreach ($requiredMethods as $m) {
        if (in_array($m, $allowedExceptions)) {
            continue;
        }
        // This is always true, for any method name, because of `__call`, rendering the test mostly useless.
        Assert::true(is_callable([new Dash([]), $m]));

        $method = $dashMethods[$m] ?? null;
        Assert::notNull($method, "Method `$m` is NOT type-hinted in the doc-comment for " . Dash::class);
        Assert::false($method['isStatic']);
    }

    // IteraFn
    $fnMethods = $extractMethods(IteraFn::class);
    $allowedPartiallyAppliedExceptions = [
        'make',
        'produce',
    ];
    foreach ($requiredMethods as $m) {
        if (in_array($m, $allowedPartiallyAppliedExceptions)) {
            continue;
        }
        // This is always true, for any method name, because of `__callStatic`, rendering the test mostly useless.
        Assert::true(is_callable(sprintf('%s::%s', IteraFn::class, $m)));

        $method = $fnMethods[$m] ?? null;
        Assert::notNull($method, "Method `$m` is NOT type-hinted in the doc-comment for " . IteraFn::class);
        Assert::true($method['isStatic']);
    }
})();
