<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

(function () {
    $collection = Dash::collect(['zero', 'one', 'two', 'three',])
        ->alter(function (iterable $collection): iterable {
            foreach ($collection as $k => $v) {
                yield $k * 2 => $v . ' suffix';
            }
        })
        ->alter(function (iterable $collection): iterable {
            foreach ($collection as $k => $v) {
                yield $k + 1 => 'prefix ' . $v;
            }
        });
    Assert::same([
        1 => 'prefix zero suffix',
        3 => 'prefix one suffix',
        5 => 'prefix two suffix',
        7 => 'prefix three suffix',
    ], $collection->toArray());
})();

(function () {
    $input = ['zero', 'one', 'two', 'three',];

    // no aggregator
    Assert::same($input, Dash::collect($input)->out());

    // with aggregator
    $keySum = Dash::collect($input)
        ->aggregate(function (iterable $collection): int {
            $keySum = 0;
            foreach ($collection as $k => $v) {
                $keySum += $k;
            }
            return $keySum;
        });
    Assert::same(6, $keySum);
})();
