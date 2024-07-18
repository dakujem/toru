<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;
use Tester\Assert;

require_once __DIR__ . '/../vendor/autoload.php';

(function () {
    $counter = 0;
    $iterable = Itera::limit(Itera::produce(function () use (&$counter) {
        $counter += 1;
        return 42;
    }), 3);
    Assert::same([42, 42, 42], Itera::toArray($iterable), 'produce values calling the factory N times');
    Assert::same(3, $counter, 'the counter should be 3');
})();

(function () {
    $counter = 0;
    $iterable = Itera::limit(Itera::produce(function () use (&$counter) {
        $counter += 1;
        return 'foo';
    }), 2);
    Assert::same(0, $counter, 'the counter should be zero (not used yet)');
    Assert::same(['foo', 'foo'], Itera::toArray($iterable), 'produce values calling the factory N times');
    Assert::same(2, $counter, 'the counter should be 2');
})();


