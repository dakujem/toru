<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\IteraFn;
use Dakujem\Toru\Pipeline;
use Tester\Assert;
use Tester\Environment;
use Tests\Support\Call;
use Tests\Support\DashTest;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

$collection = [
    'a' => 0,
    'b' => 1,
    'c' => 2,
    'd' => 3,
    'e' => 4,
    'f' => 5,
    'g' => 6,
    'h' => 7,
    'i' => 8,
    'j' => 9,
    'k' => 10,
];
$predicate = fn($i) => 0 == $i % 2;
$mapper = fn($i) => $i * 100;
$reducer = fn($carry, $i) => $carry + $i;
$initial = 10_000;
$expectArray = [0, 200, 400, 600, 800, 1_000];
$expectSum = 13_000;

(function () use ($collection, $predicate, $mapper, $reducer, $initial, $expectArray, $expectSum) {
    $result = Dash::collect($collection)
        ->filter($predicate)
        ->apply($mapper)
        ->valuesOnly()
        ->toArray();
    Assert::same($expectArray, $result);

    $sum = Dash::collect($collection)
        ->filter($predicate)
        ->apply($mapper)
        ->valuesOnly()
        ->reduce($reducer, $initial);
    Assert::same($expectSum, $sum);
})();

(function () use ($collection, $predicate, $mapper, $reducer, $initial, $expectArray, $expectSum) {
    $result = Pipeline::through(
        $collection,
        IteraFn::filter($predicate),
        IteraFn::apply($mapper),
        IteraFn::valuesOnly(),
        IteraFn::toArray(),
    );
    Assert::same($expectArray, $result);

    $sum = Pipeline::through(
        $collection,
        IteraFn::filter($predicate),
        IteraFn::apply($mapper),
        IteraFn::valuesOnly(),
        IteraFn::reduce($reducer, $initial),
    );
    Assert::same($expectSum, $sum);
})();

(function () use ($collection, $predicate, $mapper, $reducer, $initial, $expectArray, $expectSum) {
    DashTest::assert(
        [
            new Call('filter', predicate: $predicate),
            new Call('apply', values: $mapper),
            new Call('valuesOnly'),
            new Call('toArray'),
        ],
        fn(mixed $out, ?string $description) => Assert::same($expectArray, $out, $description),
        $collection,
        'Test pipeline',
    );

    DashTest::assert(
        [
            new Call('filter', predicate: $predicate),
            new Call('apply', values: $mapper),
            new Call('valuesOnly'),
            new Call('reduce', reducer: $reducer, initial: $initial),
        ],
        fn(mixed $out, ?string $description) => Assert::same($expectSum, $out, $description),
        $collection,
        'Test pipeline',
    );
})();

(function () use ($collection, $predicate, $mapper, $reducer, $initial, $expectArray, $expectSum) {
    Assert::type(Iterator::class, Dash::collect([])->toIterator());
    Assert::type(Traversable::class, Dash::collect([])->ensureTraversable());
})();
