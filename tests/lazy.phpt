<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Tester\Assert;

require_once __DIR__ . '/../vendor/autoload.php';

(function () {
    $mapCounter = 0;
    $mapper = function (mixed $value) use (&$mapCounter): mixed {
        $mapCounter += 1;
        return $value;
    };
    $filterCounter = 0;
    $filter = function () use (&$filterCounter): bool {
        $filterCounter += 1;
        return true;
    };

    $base = new class implements IteratorAggregate {
        public int $counter = 0;

        public function getIterator(): Traversable
        {
            $this->counter += 1;
            return new ArrayIterator(
                ['zero', 'one', 'two', 'three',]
            );
        }
    };

    // sanity test
    Assert::same(0, $mapCounter);
    Assert::same(0, $filterCounter);
    Assert::same(0, $base->counter);

    $collected = Dash::collect($base);
    Assert::same(0, $mapCounter);
    Assert::same(0, $filterCounter);
    Assert::same(0, $base->counter);

    // I expect the filter and mapper counters NOT to increase just yet, both the calls must be lazy.
    // Neither the internal one should.
    $fm = $collected->filter($filter)->map($mapper);
    Assert::same(0, $mapCounter);
    Assert::same(0, $filterCounter);
    Assert::same(0, $base->counter);

    // I expect ONLY the internal counter to increase.
    // Iterating over the original collection should not increment the mapper/filter counters:
    foreach ($collected as $foo) {
        $foo; // do nothing
    }
    Assert::same(0, $mapCounter);
    Assert::same(0, $filterCounter);
    Assert::same(1, $base->counter);

    // Iterating over the decorated collection should increment the filter/mapper counters once per each iteration;
    // and the internal counter only once.
    $i = 0;
    foreach ($fm as $k => $v) {
        $i += 1;
        Assert::same(0 + $i, $mapCounter);
        Assert::same(0 + $i, $filterCounter);
    }
    Assert::same(2, $base->counter);

    // ----

    // Sanity test - iteration over the collected collection should be possible (no generator in use)...
    foreach ($collected as $foo) {
        $foo; // do nothing
    }
    Assert::same(3, $base->counter);

    // ... but iteration over the decorated collection should NOT be possible, because the generator has been spent.
    Assert::exception(function () use ($fm): void {
        foreach ($fm as $foo) {
            $foo;
        }
    }, \Exception::class, 'Cannot traverse an already closed generator');
})();
