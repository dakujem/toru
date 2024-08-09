<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Itera;
use Dakujem\Toru\IteraFn;
use Dakujem\Toru\Regenerator;
use Tester\Assert;
use Tester\Environment;
use Tests\Support\Call;
use Tests\Support\DashTest;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

(function () {
    Assert::same([3, 4], Itera::toArray(Itera::chain([1, 2], [3, 4])));
    Assert::same([1, 2, 3, 4], Itera::toArray(Itera::chain([1, 2], [2 => 3, 4]))); // different indexes in the input
    Assert::same([1, 2, 3, 4], Itera::toArrayValues(Itera::chain([1, 2], [3, 4]))); // array-values
})();

(function () {
    Assert::same([], Itera::toArray(
        Itera::chain([], [], (fn() => yield from [])())
    ));
})();

(function () {
    DashTest::assert(
        [
            new Call(
                'chain',
                [3, 4, 5],
                new Regenerator(fn() => Itera::make('foo', 'bar', foo: 'bar')),
                new Regenerator(fn() => yield 'yes' => 'no')
            ),
        ],
        function (iterable $collection, ?string $desc) {
            $values = $keys = [];
            foreach ($collection as $key => $el) {
                $values[] = $el;
                $keys[] = $key;
            }
            Assert::same([1, 2, 3, 4, 5, 'foo', 'bar', 'bar', 'no'], $values);
            Assert::same([0, 1, 0, 1, 2, 0, 1, 'foo', 'yes'], $keys);
        },
        [1, 2],
    );
})();

(function () {
    // `append` is not defined on `Itera`...
    Assert::throws(fn() => Itera::append('foo'), Error::class);

    // ... but is supported for chained call by `Dash` and for partial application by `IteraFn`.
    DashTest::assert(
        [
            new Call(
                'append',
                [3, 4, 5],
            ),
        ],
        function (iterable $collection, ?string $desc) {
            $values = $keys = [];
            foreach ($collection as $key => $el) {
                $values[] = $el;
                $keys[] = $key;
            }
            Assert::same([1, 2, 3, 4, 5,], $values, $desc . ' (values)');
            Assert::same([0, 1, 0, 1, 2,], $keys, $desc . ' (keys)');
        },
        [1, 2],
        null,
        [Dash::class, IteraFn::class],
    );
})();
