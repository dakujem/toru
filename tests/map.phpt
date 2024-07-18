<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Itera;
use Dakujem\Toru\IteraFn;
use Tester\Assert;
use Tests\Support\Call;
use Tests\Support\DashTest;

require_once __DIR__ . '/../vendor/autoload.php';


(function () {
    DashTest::assert(
        callChain: [
            new Call(
                method: 'adjust',
                values: fn($v, $k) => 'the value is ' . $v . ' and the key is ' . $k,
                keys: fn($v, $k) => 'the key is ' . $k . ' and the value is ' . $v,
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same([], Itera::toArray($result), $desc);
        },
        input: [],
        description: 'Results in empty array anyway',
    );

    DashTest::assert(
        callChain: [
            new Call(
                method: 'adjust',
                values: fn($v, $k) => 'the value is ' . $v . ' and the key is ' . $k,
                keys: fn($v, $k) => 'the key is ' . $k . ' and the value is ' . $v,
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same([
                'the key is 0 and the value is zero' => 'the value is zero and the key is 0',
                'the key is 1 and the value is one' => 'the value is one and the key is 1',
                'the key is 2 and the value is two' => 'the value is two and the key is 2',
                'the key is 3 and the value is three' => 'the value is three and the key is 3',
            ], Itera::toArray($result), $desc);
        },
        input: ['zero', 'one', 'two', 'three',],
        description: 'Should map values and keys based on values and keys',
    );
})();

(function () {
    DashTest::assert(
        callChain: [
            new Call(
                method: 'apply',
                values: fn() => 'whateva',
            ),
            new Call(
                method: 'reindex',
                keys: fn() => 'meh',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same([], Itera::toArray($result), $desc);
        },
        input: [],
        description: 'Results in empty array anyway',
    );

    // Note that calling `reindex` and `apply` in a chain is not the same as calling `adjust`, because of the keys/values being updated by the method that comes prior.
    DashTest::assert(
        callChain: [
            new Call(
                method: 'reindex',
                keys: fn($v, $k) => 'the key for ' . $v . ' is ' . $k,
            ),
            new Call(
                method: 'apply',
                values: fn($v, $k) => 'but the key for ' . $v . ' has since changed to "' . $k . '"',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same(
                [
                    'the key for zero is 0' => 'but the key for zero has since changed to "the key for zero is 0"',
                    'the key for one is 1' => 'but the key for one has since changed to "the key for one is 1"',
                    'the key for two is 2' => 'but the key for two has since changed to "the key for two is 2"',
                    'the key for three is 3' => 'but the key for three has since changed to "the key for three is 3"',
                ],
                Itera::toArray($result),
                $desc,
            );
        },
        input: ['zero', 'one', 'two', 'three',],
        description: 'Should map values and keys based on values and keys, separately',
    );

    // Actually we may use the same test for `adjust`.
    DashTest::assert(
        callChain: [
            new Call(
                method: 'adjust',
                keys: fn($v, $k) => 'the key for ' . $v . ' is ' . $k,
            ),
            new Call(
                method: 'adjust',
                values: fn($v, $k) => 'but the key for ' . $v . ' has since changed to "' . $k . '"',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same(
                [
                    'the key for zero is 0' => 'but the key for zero has since changed to "the key for zero is 0"',
                    'the key for one is 1' => 'but the key for one has since changed to "the key for one is 1"',
                    'the key for two is 2' => 'but the key for two has since changed to "the key for two is 2"',
                    'the key for three is 3' => 'but the key for three has since changed to "the key for three is 3"',
                ],
                Itera::toArray($result),
                $desc,
            );
        },
        input: ['zero', 'one', 'two', 'three',],
        description: 'adjusting keys and then adjusting values separately',
    );
})();

(function () {
    // Calling `adjust` without parameters returns the same iterable.
    $input = Itera::make('zero', 'one', 'two', 'three');
    DashTest::assert(
        callChain: [
            new Call(
                method: 'adjust',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) use ($input) {
            Assert::same(
                $input,
                $result,
                $desc,
            );
        },
        input: $input,
        description: 'Should map values and keys based on values and keys',
        subjects: [Itera::class, IteraFn::class],
    );

    $array = Itera::toArray($input);
    Assert::same(
        $array,
        Dash::collect($array)->adjust()->toArray(),
    );
})();

(function () {
    // `map` and `apply` do the same
    DashTest::assert(
        callChain: [
            new Call(
                method: 'map',
                values: fn($v, $k) => 'the value at ' . $k . ' is ' . $v,
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same(
                [
                    0 => 'the value at 0 is zero',
                    1 => 'the value at 1 is one',
                    2 => 'the value at 2 is two',
                    3 => 'the value at 3 is three',
                ],
                Itera::toArray($result),
                $desc,
            );
        },
        input: ['zero', 'one', 'two', 'three',],
        description: 'Should map values based on values and keys',
    );
    DashTest::assert(
        callChain: [
            new Call(
                method: 'apply',
                values: fn($v, $k) => 'the value at ' . $k . ' is ' . $v,
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same(
                [
                    0 => 'the value at 0 is zero',
                    1 => 'the value at 1 is one',
                    2 => 'the value at 2 is two',
                    3 => 'the value at 3 is three',
                ],
                Itera::toArray($result),
                $desc,
            );
        },
        input: ['zero', 'one', 'two', 'three',],
        description: 'Should map values based on values and keys',
    );
})();

(function () {
    /* @see Itera::unfold() */
    DashTest::assert(
        callChain: [
            new Call(
                method: 'unfold',
                mapper: fn($v, $k): iterable => Itera::limit(Itera::repeat($v), $k), // the mapper returns an iterable
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same(
                [
                    // 'zero' -- zero is repeated zero times, that means it is omitted
                    'one',
                    'two',
                    'two',
                    'three',
                    'three',
                    'three',
                ],
                Itera::toArrayValues($result), // values only, the keys overlap
                $desc,
            );
        },
        input: [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
        ],
        description: 'Repeat each value key-number of times.',
    );

    // use unfold to map keys and values using the same callable
    DashTest::assert(
        callChain: [
            new Call(
                method: 'unfold',
                mapper: function (string $v): iterable {
                    $pieces = explode(':', $v);
                    // We may choose to either return an array with a single element or yield once,
                    // it results in the same transformation.
                    yield $pieces[0] => $pieces[1];
                },
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same(
                [
                    'a' => 'Adam',
                    'b' => 'Betty',
                    'c' => 'Claire',
                    'd' => 'Daniel',
                ],
                Itera::toArray($result),
                $desc,
            );
        },
        input: [
            'a:Adam',
            'b:Betty',
            'c:Claire',
            'd:Daniel',
        ],
        description: 'Using a single callable, break the value in two parts and use them to create a new collection, specifying keys and values',
    );
})();
