<?php

declare(strict_types=1);

namespace Dakujem\Toru;

use Dakujem\Toru\Exceptions\BadMethodCallException;
use Iterator;
use IteratorAggregate;
use Traversable;

/**
 * A wrapper for iterable collections that supports fluent decorations.
 *
 * Note that this wrapper is immutable and each call will decorate the input collection and wrap it again.
 * This approach enables chained calls, but prevents variable mutation or side effects.
 * However, the immutability does not extend to the input collection. If iterated, the cursor will be updated.
 * This is especially of note when wrapping generators.
 *
 * The methods have the same functionality as their counterparts in the `Itera` class.
 * The signatures are also the same, except the first parameter (`$input`), which is omitted and the wrapped collection is used instead.
 * @see Itera
 */
class Dash implements IteratorAggregate
{
    public function __construct(
        protected iterable $collection,
    ) {
    }

    /**
     * Creates and returns a new instance of self or any extending class (new static).
     */
    final public static function collect(iterable $collection): static
    {
        return new static($collection);
    }

    /**
     * Alter the collection as a whole using a decorator with the signature `fn(iterable $collection):iterable`.
     * The result is wrapped into a new wrapper instance and returned.
     * This is useful as an extension point, to implement decorations not directly provided
     * by this wrapper without extending the class.
     */
    final public function alter(callable $decorator): static
    {
        return new static(
            $decorator($this->collection)
        );
    }

    /**
     * Pass the collection as a whole through the aggregate function and return the result.
     *
     * The aggregate function should have the signature `fn(iterable $collection):mixed`.
     * The result is returned as-is, without wrapping it into a new wrapper instance.
     *
     * This is a counterpart to the `alter` method that always wraps the result.
     */
    final public function aggregate(callable $aggregate): mixed
    {
        return $aggregate($this->collection);
    }

    /**
     * Return the wrapped collection as-is.
     */
    final public function out(): iterable
    {
        return $this->collection;
    }

    //
    // The following methods decorate the wrapped iterable creating a new iterable object
    // (a Generator in most cases), returning a new wrapper instance for fluency.
    //

    /**
     * @see Itera::chain()
     */
    public function chain(iterable ...$more): static
    {
        return new static(Itera::chain($this->collection, ...$more));
    }

    /**
     * Alias for `chain`.
     * @see Itera::chain()
     */
    public function append(iterable ...$more): static
    {
        return self::chain(...$more);
    }

    /**
     * @see Itera::adjust()
     */
    public function adjust(?callable $values = null, ?callable $keys = null): static
    {
        return new static(Itera::adjust($this->collection, $values, $keys));
    }

    /**
     * @see Itera::apply()
     */
    public function apply(callable $values): static
    {
        return new static(Itera::apply($this->collection, $values));
    }

    /**
     * Alias for `apply`.
     * @see Itera::map()
     */
    public function map(callable $values): static
    {
        return new static(Itera::map($this->collection, $values));
    }

    /**
     * @see Itera::reindex()
     */
    public function reindex(callable $keys): static
    {
        return new static(Itera::reindex($this->collection, $keys));
    }

    /**
     * @see Itera::filter()
     */
    public function filter(callable $predicate): static
    {
        return new static(Itera::filter($this->collection, $predicate));
    }

    /**
     * @see Itera::limit()
     */
    public function limit(int $limit): static
    {
        return new static(Itera::limit($this->collection, $limit));
    }

    /**
     * @see Itera::omit()
     */
    public function omit(int $count): static
    {
        return new static(Itera::omit($this->collection, $count));
    }

    /**
     * @see Itera::slice()
     */
    public function slice(int $offset, int $limit): static
    {
        return new static(Itera::slice($this->collection, $offset, $limit));
    }

    /**
     * @see Itera::tap()
     */
    public function tap(callable $effect): static
    {
        return new static(Itera::tap($this->collection, $effect));
    }

    /**
     * Alias for `tap`.
     * @see Itera::each()
     */
    public function each(callable $effect): static
    {
        return new static(Itera::each($this->collection, $effect));
    }

    /**
     * @see Itera::unfold()
     */
    public function unfold(callable $mapper): static
    {
        return new static(Itera::unfold($this->collection, $mapper));
    }

    /**
     * @see Itera::valuesOnly()
     */
    public function valuesOnly(): static
    {
        return new static(Itera::valuesOnly($this->collection));
    }

    /**
     * @see Itera::keysOnly()
     */
    public function keysOnly(): static
    {
        return new static(Itera::keysOnly($this->collection));
    }

    /**
     * @see Itera::flip()
     */
    public function flip(): static
    {
        return new static(Itera::flip($this->collection));
    }

    /**
     * @see Itera::repeat()
     */
    public function repeat(): static
    {
        return new static(Itera::repeat($this->collection));
    }

    /**
     * @see Itera::loop()
     */
    public function loop(): static
    {
        return new static(Itera::loop($this->collection));
    }

    /**
     * @see Itera::replicate()
     */
    public function replicate(int $times): static
    {
        return new static(Itera::replicate($this->collection, $times));
    }

    /**
     * Special case for the `reduce` method to allow chained matrix reductions.
     * If the reducer returns an iterable type (array or Traversable), it will be wrapped as a Dash collection for fluency;
     * if it returns any other value type, it will be returned as-is.
     * @see Itera::reduce()
     */
    public function reduce(callable $reducer, mixed $initial = null): mixed
    {
        $reduction = Itera::reduce($this->collection, $reducer, $initial);
        return is_iterable($reduction) ? new static($reduction) : $reduction;
    }

    //
    // The following methods immediately iterate the collection and evaluate all decorators,
    // returning a value directly (not a wrapper).
    //

    /**
     * @see Itera::toArray()
     */
    public function toArray(): array
    {
        return Itera::toArray($this->collection);
    }

    /**
     * @see Itera::toArrayValues()
     */
    public function toArrayValues(): array
    {
        return Itera::toArrayValues($this->collection);
    }

    /**
     * @see Itera::toArrayMerge()
     */
    public function toArrayMerge(): array
    {
        return Itera::toArrayMerge($this->collection);
    }

    /**
     * @see Itera::toIterator()
     */
    public function toIterator(): Iterator
    {
        return Itera::toIterator($this->collection);
    }

    /**
     * @see Itera::count()
     */
    public function count(): int
    {
        return Itera::count($this->collection);
    }

    /**
     * @see Itera::search()
     */
    public function search(callable $predicate, mixed $default = null): mixed
    {
        return Itera::search($this->collection, $predicate, $default);
    }

    /**
     * @see Itera::searchOrFail()
     */
    public function searchOrFail(callable $predicate): mixed
    {
        return Itera::searchOrFail($this->collection, $predicate);
    }

    /**
     * @see Itera::firstValue()
     */
    public function firstValue(): mixed
    {
        return Itera::firstValue($this->collection);
    }

    /**
     * @see Itera::firstKey()
     */
    public function firstKey(): mixed
    {
        return Itera::firstKey($this->collection);
    }

    /**
     * @see Itera::firstValueOrDefault()
     */
    public function firstValueOrDefault(mixed $default = null): mixed
    {
        return Itera::firstValueOrDefault($this->collection, $default);
    }

    /**
     * @see Itera::firstKeyOrDefault()
     */
    public function firstKeyOrDefault(mixed $default = null): mixed
    {
        return Itera::firstKeyOrDefault($this->collection, $default);
    }

    /**
     * Calling `ensureTraversable` makes little sense, but let's tolerate it.
     * This instance is traversable, so the call is optimized by directly returning self.
     * @see Itera::ensureTraversable()
     */
    public function ensureTraversable(): static
    {
        return $this;
    }

    /**
     * This class may be extended to add new methods.
     * Any of the methods forwarding to the `Itera` class
     * may be overridden in the extending class to change the default behaviour.
     *
     * Calls to unsupported methods are routed here to produce a helpful hint.
     */
    public function __call(string $name, array $arguments): mixed
    {
        $hint = static::_hint($name, $arguments);
        throw new BadMethodCallException(
            sprintf('Invalid call to `%s::%s`.', static::class, $name) .
            (null !== $hint ? ' ' . $hint : '')
        );
    }

    protected static function _hint(string $name, array $arguments): ?string
    {
        if (
            'make' === $name ||
            'produce' === $name
        ) {
            return sprintf(
                'The method is not supported by the `%s` wrapper. Instead, call the static `%s::%s()` method, then wrap the result.',
                static::class, Itera::class, $name,
            );
        }
        if ('values' === $name) {
            return sprintf('Did you mean `%s::%s`?', static::class, 'valuesOnly');
        }
        if ('keys' === $name) {
            return sprintf('Did you mean `%s::%s`?', static::class, 'keysOnly');
        }
        if ('find' === $name || 'findOrDefault' === $name) {
            return sprintf('Did you mean `%s::%s`?', static::class, 'search');
        }
        if ('findOrFail' === $name) {
            return sprintf('Did you mean `%s::%s`?', static::class, 'searchOrFail');
        }
        return sprintf('To include custom decorators in the chain, `%s::alter()` or `%s::aggregate()` may be used.', static::class, static::class);
    }

    public function getIterator(): Traversable
    {
        return Itera::ensureTraversable($this->collection);
    }
}
