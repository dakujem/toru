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
    function foo()
    {
        yield new DateTime() => 'now';
        yield DateTime::createFromFormat("Y-m-d H:i", "2022-08-25 14:18") => 'that time';
    }

    Assert::throws(fn() => Itera::toArray(foo()), TypeError::class);

    Assert::throws(fn() => Itera::toArrayMerge(foo()), TypeError::class);

    Itera::toArray(Itera::flip(foo()));
})();

(function () {
    $first = [
        'zero',
        'a' => 'Adam',
        'b' => 'Betty',
        'c' => 'Claire',
        'd' => 'Daniel',
        'one',
    ];
    $second = [
        'too zero',
        'a' => 'Alder',
        'too one',
        'b' => 'Bron',
        'too too two',
    ];
    $third = [
        42 => 'fourty two',
        true => 'third one (1)',
        false => 'false zero',
    ];
    $nulls = [
        null => 'null zero',
    ];
    $empty = [
        '' => 'empty zero',
    ];

    // sanity test
    Assert::same($first, Itera::toArray($first));
    Assert::same($second, Itera::toArray($second));
    Assert::same($third, Itera::toArray($third));
    Assert::same($nulls, Itera::toArray($nulls));
    Assert::same($empty, Itera::toArray($empty));

    // sanity test
    Assert::same($first, Itera::toArrayMerge($first));
    Assert::same($second, Itera::toArrayMerge($second));
    Assert::same($third, Itera::toArrayMerge($third));
    Assert::same($nulls, Itera::toArrayMerge($nulls));
    Assert::same($empty, Itera::toArrayMerge($empty));

    // sanity test
    Assert::same(array_values($first), Itera::toArrayValues($first));
    Assert::same(array_values($second), Itera::toArrayValues($second));
    Assert::same(array_values($third), Itera::toArrayValues($third));
    Assert::same(array_values($nulls), Itera::toArrayValues($nulls));
    Assert::same(array_values($empty), Itera::toArrayValues($empty));

    DashTest::assert(
        [
            new Call('chain', $second, $third),
            new Call('toArrayMerge'),
        ],
        function (mixed $out, ?string $desc) use ($first, $second, $third): void {
            Assert::same(array_merge($first, $second, $third), $out, $desc);
        },
        $first,
        'Should be the same as array_merge',
    );
    DashTest::assert(
        [
            new Call('chain', $third, $second),
            new Call('toArrayMerge'),
        ],
        function (mixed $out, ?string $desc) use ($first, $second, $third): void {
            Assert::same(array_merge($first, $third, $second), $out, $desc);
        },
        $first,
        'Should be the same as array_merge',
    );
    DashTest::assert(
        [
            new Call('chain', $nulls),
            new Call('toArrayMerge'),
        ],
        function (mixed $out, ?string $desc) use ($first, $nulls): void {
            Assert::same(array_merge($first, $nulls), $out, $desc);
        },
        $first,
        'Should be the same as array_merge',
    );
    DashTest::assert(
        [
            new Call('chain', $empty),
            new Call('toArrayMerge'),
        ],
        function (mixed $out, ?string $desc) use ($first, $empty): void {
            Assert::same(array_merge($first, $empty), $out, $desc);
        },
        $first,
        'Should be the same as array_merge',
    );

    DashTest::assert(
        [
            new Call('chain', $second, $third),
            new Call('toArray'),
        ],
        function (mixed $out, ?string $desc) use ($first, $second, $third): void {
            Assert::same(array_replace($first, $second, $third), $out, $desc);
        },
        $first,
        'Should be the same as array_replace',
    );
    DashTest::assert(
        [
            new Call('chain', $third, $second),
            new Call('toArray'),
        ],
        function (mixed $out, ?string $desc) use ($first, $second, $third): void {
            Assert::same(array_replace($first, $third, $second), $out, $desc);
        },
        $first,
        'Should be the same as array_replace',
    );
    DashTest::assert(
        [
            new Call('chain', $nulls),
            new Call('toArray'),
        ],
        function (mixed $out, ?string $desc) use ($first, $nulls): void {
            Assert::same(array_replace($first, $nulls), $out, $desc);
        },
        $first,
        'Should be the same as array_replace',
    );
    DashTest::assert(
        [
            new Call('chain', $empty),
            new Call('toArray'),
        ],
        function (mixed $out, ?string $desc) use ($first, $empty): void {
            Assert::same(array_replace($first, $empty), $out, $desc);
        },
        $first,
        'Should be the same as array_replace',
    );

    DashTest::assert(
        [
            new Call('chain', $second, $third),
            new Call('toArrayValues'),
        ],
        function (mixed $out, ?string $desc) use ($first, $second, $third): void {
            Assert::same(array_merge(array_values($first), array_values($second), array_values($third)), $out, $desc);
        },
        $first,
        'Should be the same as array_values, then merging',
    );
    DashTest::assert(
        [
            new Call('chain', $third, $second),
            new Call('toArrayValues'),
        ],
        function (mixed $out, ?string $desc) use ($first, $second, $third): void {
            Assert::same(array_merge(array_values($first), array_values($third), array_values($second)), $out, $desc);
        },
        $first,
        'Should be the same as array_values, then merging',
    );
    DashTest::assert(
        [
            new Call('chain', $nulls),
            new Call('toArrayValues'),
        ],
        function (mixed $out, ?string $desc) use ($first, $nulls): void {
            Assert::same(array_merge(array_values($first), array_values($nulls)), $out, $desc);
        },
        $first,
        'Should be the same as array_values, then merging',
    );
    DashTest::assert(
        [
            new Call('chain', $empty),
            new Call('toArrayValues'),
        ],
        function (mixed $out, ?string $desc) use ($first, $empty): void {
            Assert::same(array_merge(array_values($first), array_values($empty)), $out, $desc);
        },
        $first,
        'Should be the same as array_values, then merging',
    );
})();
