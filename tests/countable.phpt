<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

class Bar implements IteratorAggregate
{
    public function getIterator(): Traversable
    {
        return new ArrayIterator([1, 4, 5, 3, 2]);
    }
}

class Foo extends Bar implements Countable
{
    public function count(): int
    {
        return 42;
    }
}

(function () {
    // Foo is countable
    $foo = new Foo();
    Assert::same(5, iterator_count($foo));
    Assert::same(42, count($foo));
    Assert::same(42, Itera::count($foo));

    // ... while Bar is not.
    $bar = new Bar();
    Assert::same(5, iterator_count($bar));
    Assert::throws(fn() => count($bar), TypeError::class);
    Assert::same(5, Itera::count($bar));
})();

(function () {
    $input = [
        'a' => 'Adam',
        'b' => 'Betty',
        'c' => 'Claire',
        'd' => 'Daniel',
    ];
    $iterable = Itera::make(...$input);
    Assert::same(count($input), Itera::count($iterable));
})();
