<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;
use Tester\Assert;
use Tester\Environment;
use Tests\Support\Call;
use Tests\Support\DashTest;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

(function () {
    $input = [
        'a' => 'Adam',
        'b' => 'Betty',
        'c' => 'Claire',
        'd' => 'Daniel',
    ];
    $counter = 0;
    $iterable = Itera::tap($input, function () use (&$counter) {
        $counter += 1;
        return 42;
    });
    Assert::same($input, Itera::toArray($iterable), 'tap must not modify the input');
    Assert::same(count($input), $counter, 'the counter must be equal to the size of the input');
})();

(function () {
    $input = [
        'a' => 'Adam',
        'b' => 'Betty',
        'c' => 'Claire',
        'd' => 'Daniel',
    ];
    $collector = [];
    $iterable = Itera::each($input, function ($v, $k) use (&$collector) {
        $collector[$k] = $v;
        return 42;
    });
    Assert::same([], $collector, 'empty at this point');
    Assert::same($input, Itera::toArray($iterable), 'run the tap function, do not change the input');
    Assert::same($input, $collector, 'the collected array must be the same');
})();

(function () {
    $input = [
        'a' => 'Adam',
        'b' => 'Betty',
        'c' => 'Claire',
        'd' => 'Daniel',
    ];
    DashTest::assert(
        callChain: [
            new Call(
                method: 'tap',
                effect: fn($v) => $v = 4,
            ),
            new Call(
                method: 'each',
                effect: fn($v, $k) => 'foobar',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) use ($input) {
            Assert::same($input, Itera::toArray($result), 'tap must not modify the input');
        },
        input: $input,
        description: 'neither tap nor each must not modify the input',
    );
})();

// edge case! using ref to modify the input values
(function () {
    $input = [
        'a' => 'Adam',
        'b' => 'Betty',
        'c' => 'Claire',
        'd' => 'Daniel',
    ];
    DashTest::assert(
        callChain: [
            new Call(
                method: 'tap',
                effect: fn(&$v) => $v = 4, // reference!
            ),
        ],
        assertion: function (iterable $result, ?string $desc) use ($input) {
            Assert::same(
                $input = [
                    'a' => 4,
                    'b' => 4,
                    'c' => 4,
                    'd' => 4,
                ], Itera::toArray($result), 'using reference screws the whole point');
        },
        input: $input,
        description: 'just do not do this please',
    );
})();