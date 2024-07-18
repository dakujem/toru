<?php

declare(strict_types=1);

use Dakujem\Toru\Pipeline;
use Tester\Assert;

require_once __DIR__ . '/../vendor/autoload.php';

(function () {
    // empty pipeline will do nothing
    Assert::same(42, Pipeline::throughStages(42, []));
    Assert::same('foo', Pipeline::throughStages('foo', []));
    Assert::same($dt = new DateTime(), Pipeline::throughStages($dt, []));
    Assert::same(null, Pipeline::throughStages(null, []));
})();

(function () {
    // the pipeline will do nothing, the result must not be modified
    $stages = [
        fn($i) => $i,
        fn($i) => $i,
        fn($i) => $i,
        fn($i) => $i,
        fn($i) => $i,
    ];
    Assert::same(42, Pipeline::throughStages(42, $stages));
    Assert::same($dt = new DateTime(), Pipeline::throughStages($dt, $stages));
})();

(function () {
    // the pipeline will multiply by 2, then increment by 1000, then divide by 2, effectively adding 500 to the input
    $stages = [
        fn($i) => $i * 2,
        fn($i) => $i + 1_000,
        fn($i) => $i / 2,
    ];
    Assert::same(542, Pipeline::throughStages(42, $stages));
    Assert::same(542, Pipeline::through(42, ...$stages));
})();
