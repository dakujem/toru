<?php

declare(strict_types=1);

namespace Dakujem\Toru;

use Closure;
use Dakujem\Toru\Exceptions\UnexpectedValueException;
use IteratorAggregate;
use Traversable;

/**
 * An external iterator that will invoke the given callable whenever it is iterated over (i.e. rewound).
 * It is useful for wrapping generator functions directly or callables returning generator objects
 * for the sake of enabling multiple iterations.
 * This prevents issues with generators being already closed at the expense of running them again.
 *
 * The result of the callable is returned as the inner iterator.
 * If the callable results in an array, it is wrapped into an ArrayIterator.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class Regenerator implements IteratorAggregate
{
    public readonly Closure $callable; // Must be `Closure` because `callable` is not a native type yet.

    public function __construct(
        callable $callable
    ) {
        $this->callable = $callable instanceof Closure ? $callable : $callable(...);
    }

    public function getIterator(): Traversable
    {
        $collection = ($this->callable)();
        if (!is_iterable($collection)) {
            throw new UnexpectedValueException('The value returned by the provider callable is not an iterable collection.');
        }
        return Itera::ensureTraversable(
            input: $collection
        );
    }

    public function __invoke(...$args): mixed
    {
        return ($this->callable)(...$args);
    }
}
