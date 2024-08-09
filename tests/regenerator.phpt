<?php

declare(strict_types=1);

use Dakujem\Toru\Exceptions\UnexpectedValueException;
use Dakujem\Toru\Itera;
use Dakujem\Toru\Regenerator;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

(function () {
    $counter = 0;
    $generator = function () use (&$counter) {
        $counter += 1;
        yield 'ahoj';
    };

    // sanity test
    Assert::same($counter, 0);

    $gen1 = $generator();
    Assert::same($counter, 0);

    $vals = [];
    foreach ($gen1 as $value) {
        $vals[] = $value;
    }
    Assert::same(['ahoj'], $vals);
    Assert::same($counter, 1);

    $it = Itera::chain($generator());
    Assert::same(['ahoj'], iterator_to_array(Itera::ensureTraversable($it)));
    Assert::same($counter, 2);
    Assert::throws(function () use ($it) {
        // subsequent iterations will fail
        $vals = iterator_to_array(Itera::ensureTraversable($it));
    }, Throwable::class);
    Assert::same($counter, 2);


    $fixed = new Regenerator($generator);
    Assert::same($counter, 2);
    Assert::same(['ahoj'], iterator_to_array($fixed));
    Assert::same($counter, 3);

    $chained = new Regenerator(fn() => Itera::chain($generator(), ['author' => 'dakujem']));
    Assert::same($counter, 3);
    Assert::same(['ahoj', 'author' => 'dakujem'], iterator_to_array(Itera::ensureTraversable($chained)));
    Assert::same($counter, 4);
    Assert::same(['ahoj', 'author' => 'dakujem'], iterator_to_array(Itera::ensureTraversable($chained)));
    Assert::same($counter, 5);

    // also make sure the regenerator is still callable
    Assert::true(is_callable($fixed));
    Assert::type(Regenerator::class, $fixed);
    Assert::type(Generator::class, $fixed());
})();

(function () {
    $faulty = new Regenerator(fn() => 'foo');
    Assert::throws(function () use ($faulty) {
        iterator_to_array($faulty);
    }, UnexpectedValueException::class, 'The value returned by the provider callable is not an iterable collection.');
})();
