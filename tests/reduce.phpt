<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Itera;
use Tester\Assert;
use Tester\Environment;
use Tests\Support\Call;
use Tests\Support\DashTest;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

(function () {
    // sanity test - for empty arrays, array_reduce returns the initial value all the time
    Assert::same(null, array_reduce([], fn() => null));
    Assert::same(null, array_reduce([], fn() => true));
    Assert::same(null, array_reduce([], fn() => false));
    Assert::same(null, array_reduce([], fn() => 42));
    Assert::same('initial', array_reduce([], fn() => 42, 'initial'));

    DashTest::assert(
        [
            new Call('reduce', reducer: fn() => 42, initial: 'initial'),
        ],
        fn(mixed $out, ?string $description) => Assert::same('initial', $out, $description),
        [], // empty
        'Reducer always returns the initial value for empty collections',
    );
    DashTest::assert(
        [
            new Call('reduce', reducer: fn() => 42),
        ],
        fn(mixed $out, ?string $description) => Assert::same(null, $out, $description),
        [], // empty
        'Reducer always returns null for empty collections if initial si not defined',
    );

    $collection = [1, 2, 3];

    Assert::same(null, Itera::reduce($collection, fn() => null));
    Assert::same(true, Itera::reduce($collection, fn() => true));
    Assert::same(false, Itera::reduce($collection, fn() => false));
    Assert::same(0, Itera::reduce($collection, fn() => 0));

    Assert::same(null, Dash::collect($collection)->reduce(fn() => null));
    Assert::same(true, Dash::collect($collection)->reduce(fn() => true));
    Assert::same(false, Dash::collect($collection)->reduce(fn() => false));
    Assert::same(0, Dash::collect($collection)->reduce(fn() => 0));

    DashTest::assert(
        [
            new Call('reduce', reducer: fn() => 42),
        ],
        fn(mixed $out, ?string $description) => Assert::same(42, $out, $description),
        $collection,
        'Constant reducer',
    );

    // constant reducers are not really helpful in real-world scenario, but will do for the test
    DashTest::assert(
        [
            new Call('reduce', reducer: fn() => [42], initial: []),
            new Call('reduce', reducer: fn() => [1, 2, 3], initial: []),
            new Call('reduce', reducer: fn() => 'foo', initial: []),
        ],
        fn(mixed $out, ?string $description) => Assert::same('foo', $out, $description),
        [1, 2, 3], // non-empty for this test case, otherwise the constant reducers won't be triggered
        'Reducer chain',
    );
})();

(function () {
    $input = [
        [1, 2, 3],
        [4, 5, 6],
        [7, 8, 9],
    ];
    $reduceMatrix = fn(array $carry, array $value) => array_merge($carry, $value);
    Assert::same(
        [
            1, 2, 3,
            4, 5, 6,
            7, 8, 9,
        ],
        Dash::collect($input)
            ->reduce(reducer: $reduceMatrix, initial: [])
            ->toArray(),
    );
    Assert::same(
        1 + 2 + 3 +
        4 + 5 + 6 +
        7 + 8 + 9,
        Dash::collect($input)
            ->reduce(reducer: $reduceMatrix, initial: [])
            ->reduce(
                reducer: fn(int $sum, int $value) => $sum + $value,
                initial: 0,
            ),
    );
})();
