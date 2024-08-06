<?php

declare(strict_types=1);

namespace Dakujem\Toru;

use Dakujem\Toru\Exceptions\BadMethodCallException;
use Iterator;
use IteratorAggregate;

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
 *
 * The following methods decorate the wrapped iterable creating a new iterable object (a Generator in most cases),
 * returning a new wrapper instance containing the decorated iterable.
 * @method self chain(iterable ...$more) The chain method effectively appends one or more collections to the currently wrapped one.
 * @method self append(iterable ...$more) Alias for `chain`.
 *
 * @method self filter(callable $predicate)
 * @method self limit(int $limit)
 * @method self omit(int $omitted)
 * @method self slice(int $offset, int $limit)
 *
 * @method self adjust(?callable $values = null, ?callable $keys = null)
 * @method self map(callable $values) Alias for `apply`.
 * @method self apply(callable $values)
 * @method self reindex(callable $keys)
 * @method self unfold(callable $mapper)
 * @method self valuesOnly() Discards the keys (similar to `array_values`).
 * @method self keysOnly() Returns only the keys (similar to `array_keys`).
 * @method self flip()
 *
 * @method self tap(callable $effect)
 * @method self each(callable $effect) Alias for `tap`.
 *
 * @method self repeat() repeat the whole wrapped collection indefinitely
 * @method self loop() yield all elements of the wrapped collection indefinitely; watch out for key collisions (see toArrayMerge, valuesOnly)
 * @method self replicate(int $times) yield all elements of the wrapped collection exactly N times; watch out for key collisions (see toArrayMerge, valuesOnly)
 *
 * The following methods immediately iterate the collection and evaluate all decorators, returning a value.
 * @method array toArray() Preserves the original keys. Watch out for overlapping keys (including numeric keys).
 * @method array toArrayMerge() Discards the numeric keys and preserves the original associative keys. Emulates `array_merge` behaviour for overlapping keys.
 * @method array toArrayValues() Discards the keys (similar to `array_values`).
 * @method self|mixed reduce(callable $reducer, mixed $initial = null) Reduce the collection to a value. If the resulting value is of iterable type, it is wrapped into a collection before being returned to allow for fluent chaining. Other values are returned unaltered. The signature of the reducer is `fn(mixed $carry, mixed $value, mixed $key): mixed`.
 * @method mixed search(callable $predicate)
 * @method mixed searchOrFail(callable $predicate)
 * @method mixed firstValue()
 * @method mixed firstKey()
 * @method mixed firstValueOrDefault(mixed $default = null)
 * @method mixed firstKeyOrDefault(mixed $default = null)
 * @method int count()
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
class Dash implements IteratorAggregate
{
    public static ?string $wrapperClass = null;

    public function __construct(
        protected iterable $collection,
    ) {
    }

    public static function collect(iterable $collection): self
    {
        if (null !== static::$wrapperClass) {
            return new (static::$wrapperClass)($collection);
        }
        return new static($collection);
    }

    public function getIterator(): Iterator
    {
        return Itera::toIterator($this->collection);
    }

    /**
     * Alter the collection as a whole using a decorator with signature `fn(iterable $collection):iterable`.
     * The result is wrapped into a new wrapper instance and returned.
     * This is useful as an extension point, to implement decorations not directly provided by this wrapper.
     */
    public function alter(callable $decorator): self
    {
        return new static(
            $decorator($this->collection)
        );
    }

    /**
     * Pass the collection as a whole through the aggregate function and return the result.
     *
     * The aggregate function should have signature `fn(iterable $collection):mixed`.
     * The result is returned as-is, without wrapping it into a new wrapper instance.
     *
     * This is a counterpart to the `alter` method that always wraps the result.
     */
    public function aggregate(callable $aggregate): mixed
    {
        return $aggregate($this->collection);
    }

    /**
     * Return the wrapped collection as-is.
     */
    public function out(): iterable
    {
        return $this->collection;
    }

    /**
     * This class may be extended and any of the methods may be implemented directly to change the default behaviour.
     */
    public function __call(string $name, array $arguments): mixed
    {
        // These methods return directly.
        if (
            'toArray' === $name ||
            'toArrayValues' === $name ||
            'toArrayMerge' === $name ||
            'count' === $name ||
            'search' === $name ||
            'searchOrFail' === $name ||
            'firstValue' === $name ||
            'firstKey' === $name ||
            'firstValueOrDefault' === $name ||
            'firstKeyOrDefault' === $name
        ) {
            // Returning a value, not a collection.
            return Itera::{$name}($this->collection, ...$arguments);
        }

        // Special case for `reduce` method to allow chained matrix reductions.
        // If the reducer returns an iterable type (array or Traversable) it will be wrapped as a Collection for fluency;
        // if it returns any other value type it will be returned as-is.
        if ('reduce' === $name) {
            $reduction = Itera::{$name}($this->collection, ...$arguments);
            return is_iterable($reduction) ? new static($reduction) : $reduction;
        }

        // Alias for the `append` function.
        if ('append' === $name) {
            $name = 'chain';
        }

        // Methods that return iterable types get wrapped for fluency.
        if (
            'adjust' === $name ||
            'apply' === $name || // == map
            'map' === $name ||
            'reindex' === $name ||
            'filter' === $name ||
            'limit' === $name ||
            'omit' === $name ||
            'slice' === $name ||
            'chain' === $name || // == append
            'tap' === $name || // == each
            'each' === $name ||
            'unfold' === $name ||
            'valuesOnly' === $name ||
            'keysOnly' === $name ||
            'flip' === $name ||
            'repeat' === $name ||
            'loop' === $name ||
            'replicate' === $name
        ) {
            return new static(
                Itera::{$name}($this->collection, ...$arguments)
            );
        }

        // Calling these methods makes little sense, but let's tolerate it.
        if (
            'toIterator' === $name ||
            'ensureTraversable' === $name
        ) {
            return $this;
        }

        $hint = null;
        if (
            'make' === $name ||
            'produce' === $name
        ) {
            $hint = sprintf(
                'The method is not supported by the `%s` wrapper. Instead, call the static `%s::%s()` method, then wrap the result.',
                static::class, Itera::class, $name,
            );
        }
        if ('values' === $name) {
            $hint = sprintf('Did you mean `%s::%s`?', static::class, 'valuesOnly');
        }
        if ('keys' === $name) {
            $hint = sprintf('Did you mean `%s::%s`?', static::class, 'keysOnly');
        }
        if ('find' === $name || 'findOrDefault' === $name) {
            $hint = sprintf('Did you mean `%s::%s`?', static::class, 'search');
        }
        if ('findOrFail' === $name) {
            $hint = sprintf('Did you mean `%s::%s`?', static::class, 'searchOrFail');
        }
        $hint ??= sprintf('To include custom decorators in the chain, `%s::alter()` or `%s::aggregate()` may be used.', static::class, static::class);
        throw new BadMethodCallException(
            sprintf('Invalid call to `%s::%s`.', static::class, $name) . ' ' . $hint
        );
    }
}
