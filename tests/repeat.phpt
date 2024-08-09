<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Environment;
use Tests\Support\Call;
use Tests\Support\DashTest;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

(function () {
    DashTest::assert(
        callChain: [
            new Call(
                method: 'repeat',
            ),
            new Call(
                method: 'limit',
                limit: 3,
            ),
            new Call(
                method: 'toArray',
            ),
        ],
        assertion: function (mixed $result, ?string $desc) {
            Assert::same(
                [[1, 2, 42], [1, 2, 42], [1, 2, 42]],
                $result,
                $desc,
            );
        },
        input: [1, 2, 42],
        description: 'repeats the whole [1,2,42] array for 3 times, creating a matrix, using repeat and limit',
    );

    DashTest::assert(
        callChain: [
            new Call(
                method: 'replicate',
                times: 3,
            ),
            new Call(
                method: 'toArrayValues', // must be values only due to overlapping keys
            ),
        ],
        assertion: function (mixed $result, ?string $desc) {
            Assert::same(
                [1, 2, 42, 1, 2, 42, 1, 2, 42],
                $result,
                $desc,
            );
        },
        input: [1, 2, 42],
        description: 'repeats the elements of [1,2,42] array for 3 times, using replicate',
    );

    DashTest::assert(
        callChain: [
            new Call(
                method: 'loop',
            ),
            new Call(
                method: 'limit',
                limit: 3,
            ),
            new Call(
                method: 'toArrayValues',
            ),
        ],
        assertion: function (mixed $result, ?string $desc) {
            Assert::same(
                [1, 2, 42],
                $result,
                $desc,
            );
        },
        input: [1, 2, 42],
        description: 'loops the elements of [1,2,42] array, limiting to 3 elements, using loop',
    );

    DashTest::assert(
        callChain: [
            new Call(
                method: 'loop',
            ),
            new Call(
                method: 'limit',
                limit: 10,
            ),
            new Call(
                method: 'toArrayValues', // must be values only due to overlapping keys
            ),
        ],
        assertion: function (mixed $result, ?string $desc) {
            Assert::same(
                [1, 2, 42, 1, 2, 42, 1, 2, 42, 1],
                $result,
                $desc,
            );
        },
        input: [1, 2, 42],
        description: 'loops the elements of [1,2,42] array, limiting to 10 elements, using loop',
    );
})();


