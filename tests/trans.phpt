<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;
use Tester\Assert;
use Tests\Support\Call;
use Tests\Support\DashTest;

require_once __DIR__ . '/../vendor/autoload.php';


(function () {
    DashTest::assert(
        callChain: [
            new Call(
                method: 'keysOnly',
            ),
            new Call(
                method: 'valuesOnly',
            ),
            new Call(
                method: 'flip',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same([], Itera::toArray($result), $desc);
        },
        input: [],
        description: 'Results in empty array anyway',
    );

    $input = [
        'a' => 'Adam',
        'b' => 'Betty',
        'c' => 'Claire',
        'd' => 'Daniel',
    ];
    DashTest::assert(
        callChain: [
            new Call(
                method: 'keysOnly',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same([
                'a',
                'b',
                'c',
                'd',
            ], Itera::toArray($result), $desc);
        },
        input: $input,
        description: 'keys only',
    );
    DashTest::assert(
        callChain: [
            new Call(
                method: 'valuesOnly',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same([
                'Adam',
                'Betty',
                'Claire',
                'Daniel',
            ], Itera::toArray($result), $desc);
        },
        input: $input,
        description: 'values only',
    );
    DashTest::assert(
        callChain: [
            new Call(
                method: 'flip',
            ),
        ],
        assertion: function (iterable $result, ?string $desc) {
            Assert::same([
                'Adam' => 'a',
                'Betty' => 'b',
                'Claire' => 'c',
                'Daniel' => 'd',
            ], Itera::toArray($result), $desc);
        },
        input: $input,
        description: 'flip | pilf',
    );
})();

