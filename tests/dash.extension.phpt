<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;
use Dakujem\Toru\Exceptions\BadMethodCallException;
use Tester\Assert;

require_once __DIR__ . '/../vendor/autoload.php';

class Extension extends Dash
{
    public static function foo(): self
    {
        return new static(
            [1, 2, 3, 4]
        );
    }
}

(function () {
    Assert::type(Dash::class, Dash::collect([]));
    Assert::same(Dash::class, Dash::collect([])::class);
    Assert::notEqual(Extension::class, Dash::collect([])::class);

    Assert::throws(fn() => Dash::collect([])->foo(), BadMethodCallException::class);

    Dash::$wrapperClass = Extension::class;

    Assert::type(Dash::class, Dash::collect([]));
    Assert::type(Extension::class, Dash::collect([]));
    Assert::same(Extension::class, Dash::collect([])::class);

    Assert::same([1, 2, 3, 4], Dash::collect([])->foo()->toArray());
})();
