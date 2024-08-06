<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Exceptions\BadMethodCallException;
use Dakujem\Toru\Itera;
use Tester\Assert;

require_once __DIR__ . '/../vendor/autoload.php';

class Extension extends Dash
{
    public function foo(): self
    {
        return new static(
            [1, 2, 3, 4, 'foo']
        );
    }
}

class MyItera extends Itera
{
    public static function appendBar(iterable $input): iterable
    {
        return static::chain($input, ['bar' => 'bar']);
    }
}

class MyDash extends Dash
{
    public function appendFoo(): self
    {
        return new static(
            Itera::chain($this->collection, ['foo' => 'foo'])
        );
    }

    public function appendBar(): self
    {
        return new static(
            MyItera::appendBar($this->collection)
        );
    }

    public function aggregateZero(): int
    {
        return 0;
    }
}

(function () {
    Assert::type(Dash::class, Dash::collect([]));
    Assert::same(Dash::class, Dash::collect([])::class);
    Assert::notEqual(Extension::class, Dash::collect([])::class);

    Assert::throws(fn() => Dash::collect([])->foo(), BadMethodCallException::class);

    Assert::type(Dash::class, Dash::collect([]));
    Assert::type(Extension::class, Extension::collect([]));
    Assert::same(Extension::class, Extension::collect([])::class);

    Assert::same([1, 2, 3, 4, 'foo'], Extension::collect([])->foo()->toArray());
    Assert::same(Extension::class, Extension::collect([])->alter(fn() => [1, 2, 3])::class);

    Extension::collect([])->chain(['a'])->reduce(fn() => [])->foo();

    Assert::same(0, MyDash::collect([])->aggregateZero());
    Assert::same([1, 2, 3, 'foo' => 'foo', 'bar' => 'bar'], MyDash::collect([1, 2, 3])->appendFoo()->appendBar()->toArray());
})();
