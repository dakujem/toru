<?php

declare(strict_types=1);

namespace Dakujem\Toru;

use ArrayIterator;
use Countable;
use Dakujem\Toru\Exceptions\EmptyCollectionException;
use Dakujem\Toru\Exceptions\NoMatchingElementFound;
use Iterator;
use IteratorIterator;
use Traversable;

/**
 * Helper class for iterable types.
 * The `iterable` is a built-in compile time type alias for `array|Traversable` encompassing all arrays and iterators.
 *
 * This class uses native generators.
 * Methods returning `iterable` are lazy: the actual operations are done upon the subsequent iteration.
 *
 * The doc comments in class make references to `nikic/iter` package that does the same thing in certain cases,
 * but has somewhat cumbersome interface, but provides more functions for some other scenarios (e.g. `iter\slice`, `iter\take`, `iter\drop`).
 * @link https://github.com/nikic/iter
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
final class Itera
{
    /**
     * Creates a single iterable generator from multiple iterables.
     * The generator will iterate over all the elements of the first iterable, then the next, and so on.
     *
     * WARNING: If the generator is cast to array, indexes and their values might get overwritten!
     * @see self::valuesOnly()
     *
     * Note: Equivalent to `iter\chain`.
     *
     * @param iterable ...$input multiple iterables (arrays or iterators)
     * @return iterable a generator
     */
    public static function chain(iterable ...$input): iterable
    {
        foreach ($input as $iterable) {
            yield from $iterable;
        }
    }

    /**
     * Allows remapping the input collection with the option to recalculate values and/or keys.
     *
     * Hint: If a single callable is needed to alter both values and keys, use the `unfold` method:
     * @see self::unfold()
     *
     * Note: Equivalent to `iter\mapWithKeys` and `iter\reindex` (with the addition of access to keys) called in chain.
     *
     * @param callable|null $values Calculates new values; signature `fn(mixed $value, mixed $key): mixed`
     * @param callable|null $keys Calculates new keys;   signature `fn(mixed $value, mixed $key): mixed`
     * @return iterable Returns a generator, or the input unchanged.
     */
    public static function adjust(iterable $input, ?callable $values = null, ?callable $keys = null): iterable
    {
        if (null === $values && null === $keys) {
            // Map neither values nor keys.
            return $input;
        }
        if (null === $keys) {
            // Map values only.
            return self::apply($input, $values);
        }
        if (null === $values) {
            // Map keys only.
            return self::reindex($input, $keys);
        }
        // Map both values and keys.
        // Note: This method does not contain yield, thus is not a generator function itself, but returns generators.
        //       That's why the `remap` is separated into a private method.
        return self::remap($input, $values, $keys);
    }

    private static function remap(iterable $input, callable $values, callable $keys): iterable
    {
        foreach ($input as $key => $value) {
            yield $keys($value, $key) => $values($value, $key);
        }
    }

    /**
     * Remap an iterable applying a function to each element.
     *
     * Note: Equivalent to `iter\mapWithKeys`. Note that `iter\apply` serves different purpose and is not lazy.
     *
     * @param callable $values Calculates new values; signature `fn(mixed $value, mixed $key): mixed`
     * @return iterable Returns a generator.
     */
    public static function apply(iterable $input, callable $values): iterable
    {
        foreach ($input as $key => $value) {
            yield $key => $values($value, $key);
        }
    }

    /**
     * Reindex an iterable, computing a new key during each iteration.
     *
     * Note: Equivalent to `iter\reindex`, but allows access to the original key.
     *
     * @param callable $keys Calculates new keys; signature `fn(mixed $value, mixed $key): mixed`
     * @return iterable Returns a generator.
     */
    public static function reindex(iterable $input, callable $keys): iterable
    {
        foreach ($input as $key => $value) {
            yield $keys($value, $key) => $value;
        }
    }

    /**
     * Map value of each element using a callable mapper.
     * This is an alias of the `apply` method:
     * @see self::apply()
     */
    public static function map(iterable $input, callable $values): iterable
    {
        return self::apply($input, $values);
    }

    /**
     * Unfolds an iterable by applying a mapper onto each element and then yielding from the result.
     * The mapper function MUST always return an iterable type.
     *
     * Note: Equivalent to `iter\flatMap`, but with the addition of access to keys.
     */
    public static function unfold(iterable $input, callable $mapper): iterable
    {
        foreach ($input as $key => $value) {
            yield from $mapper($value, $key);
        }
    }

    /**
     * Iterates over values, ignoring keys.
     * Similar to `array_values`.
     *
     * Useful for "chained" or repeated iterables containing elements with overlapping indexes.
     * @see self::chain()
     */
    public static function valuesOnly(iterable $input): iterable
    {
        foreach ($input as $key => $value) {
            yield $value;
        }
    }

    /**
     * Iterates over keys, ignoring values.
     * Similar to `array_keys`.
     */
    public static function keysOnly(iterable $input): iterable
    {
        foreach ($input as $key => $value) {
            yield $key;
        }
    }

    /**
     * Flips keys and values as in `array_flip`.
     * @see \array_flip()
     *
     *      flip(['a' => 'Adam', 'b' => 'Betty']); // ['Adam' => 'a', 'Betty' => 'b']
     *
     * Note that unlike the native `array_flip`,
     * this method does NOT restrict the possible value types because generators may yield any type of keys.
     */
    public static function flip(iterable $input): iterable
    {
        foreach ($input as $key => $value) {
            yield $value => $key;
        }
    }

    /**
     * Creates an iterable that filters out elements failing a predicate.
     *
     * Note: Equivalent to `iter\filter`.
     */
    public static function filter(iterable $input, callable $predicate): iterable
    {
        // Note: Originally, this was implemented using CallbackFilterIterator, but changed later for consistency.
        // return new CallbackFilterIterator(self::toIterator($input), $predicate);
        foreach ($input as $key => $value) {
            if ($predicate($value, $key)) {
                yield $key => $value;
            }
        }
    }

    /**
     * Limits an iterable to a certain amount of elements.
     *
     * Note: Equivalent to `iter\take`.
     */
    public static function limit(iterable $input, int $limit): iterable
    {
        if ($limit <= 0) {
            return;
        }
        $i = 0;
        foreach ($input as $key => $value) {
            yield $key => $value;
            if (++$i >= $limit) {
                break;
            }
        }
    }

    /**
     * Omits a certain amount of elements from the beginning of an iterable.
     *
     * Note: Equivalent to `iter\drop`.
     */
    public static function omit(iterable $input, int $omit): iterable
    {
        $i = 0;
        foreach ($input as $key => $value) {
            if ($i++ >= $omit) {
                yield $key => $value;
            }
        }
    }

    /**
     * Extracts a slice of an iterable collection by applying limit and offset.
     * Negative values of neither limit nor offset are supported.
     *
     * Note: Equivalent to `iter\slice`.
     */
    public static function slice(iterable $input, int $offset, int $limit): iterable
    {
        return self::limit(self::omit($input, $offset), $limit);
    }

    /**
     * Searches for an element using a predicate.
     * Returns `null` by default when no match is found.
     *
     * Note: Equivalent to `iter\search`, but with the keys being passed to the predicate.
     *
     * @param callable $predicate signature `fn(mixed $value, mixed $key): bool`
     */
    public static function search(iterable $input, callable $predicate, mixed $default = null): mixed
    {
        try {
            return self::searchOrFail($input, $predicate);
        } catch (NoMatchingElementFound) {
            return $default;
        }
    }

    /**
     * Searches for an element using a predicate.
     * When no match is found, throws an exception.
     *
     * @param callable $predicate signature `fn(mixed $value, mixed $key): bool`
     * @throws NoMatchingElementFound
     */
    public static function searchOrFail(iterable $input, callable $predicate): mixed
    {
        foreach ($input as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }
        throw NoMatchingElementFound::fromInputAndPredicate($input, $predicate);
    }

    /**
     * Reduces an iterable to a single value.
     * Similar to `array_reduce` with the addition of access to the collection keys.
     *
     * Note: Equivalent to `iter\reduce`.
     * @see \array_reduce()
     *
     * @param callable $reducer signature fn(mixed $carry, mixed $value, mixed $key): mixed
     */
    public static function reduce(iterable $input, callable $reducer, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($input as $key => $value) {
            $carry = $reducer($carry, $value, $key);
        }
        return $carry;
    }

    /**
     * Returns the first value of an iterable.
     * @throws EmptyCollectionException for empty iterables
     */
    public static function firstValue(iterable $input): mixed
    {
        foreach ($input as $value) {
            return $value;
        }
        // Note: Throws. Any value, if returned, would be indistinguishable from a valid value (even `null` or `false`).
        throw EmptyCollectionException::fromInput($input);
    }

    /**
     * Returns the first key of an iterable.
     * @throws EmptyCollectionException for empty iterables
     *
     * Note that this can indeed return `mixed`, because generators may yield keys of any type.
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public static function firstKey(iterable $input): mixed
    {
        foreach ($input as $key => $value) {
            return $key;
        }
        // Note: Throws. Any value, if returned, would be indistinguishable from a valid key (even `null` or `false`).
        throw EmptyCollectionException::fromInput($input);
    }

    /**
     * Returns the first value of an iterable.
     * Returns the default for empty iterables.
     */
    public static function firstValueOrDefault(iterable $input, mixed $default = null): mixed
    {
        foreach ($input as $value) {
            return $value;
        }
        return $default;
    }

    /**
     * Returns the first key of an iterable.
     * Returns the default for empty iterables.
     */
    public static function firstKeyOrDefault(iterable $input, mixed $default = null): mixed
    {
        foreach ($input as $key => $value) {
            return $key;
        }
        return $default;
    }

    /**
     * Counts the number of elements in an iterable collection.
     * For objects implementing `Countable` the `count` method implementation is used.
     */
    public static function count(iterable $input): int
    {
        // Since PHP 8.2 it _should_ be enough to call `iterator_count` for arrays.
        // However, the native `count` function will invoke `Countable::count` implementation for countable objects.
        return is_array($input) || $input instanceof Countable ? count($input) : iterator_count($input);
    }

    /**
     * Creates an iterable generator that invokes a side effect for each element upon iteration.
     * The generator yields the original keys and values.
     * The return values of the effect function are ignored.
     *
     * Note: Equivalent to `iter\tap`.
     */
    public static function tap(iterable $input, callable $effect): iterable
    {
        foreach ($input as $key => $value) {
            $effect($value, $key);
            yield $key => $value;
        }
    }

    /**
     * Alias of `tap`.
     * @see self::tap()
     */
    public static function each(iterable $input, callable $effect): iterable
    {
        return self::tap($input, $effect);
    }

    /**
     * Produces a generator that iterates over the given arguments.
     * In most cases, an array will be more efficient, except when an iterator type is needed.
     */
    public static function make(mixed ...$input): Iterator
    {
        yield from $input;
    }

    /**
     * Repeatedly calls the producer and yields the result.
     * For convenience, the producer is passed the current iteration index.
     *
     * WARNING: Unless limited, this will create an endless loop! Do not cast the iterator to array.
     * @see self::limit()
     */
    public static function produce(callable $producer): iterable
    {
        $i = 0;
        while (true) {
            yield $producer($i++);
        }
    }

    /**
     * Repeats a given value indefinitely.
     *
     *      repeat('foo');   // 'foo', 'foo', 'foo', ...
     *      repeat([1,2,3]); // [1,2,3], [1,2,3], [1,2,3], ...
     *
     * Note that even if the input is iterable, it will be yielded as-is, repeatedly.
     * You may consider using `loop` or `replicate` instead.
     *
     * WARNING: Unless limited, this will create an endless loop! Do not cast the iterator to array.
     * @see self::limit()
     */
    public static function repeat(mixed $input): iterable
    {
        while (true) {
            yield $input;
        }
    }

    /**
     * Yields the elements of the input collection indefinitely in a loop.
     *
     *     loop([1,2,3]); // 1,2,3,1,2,3,1,2, ...
     *
     * WARNING: Unless limited, this will create an endless loop! Do not cast the iterator to array.
     * @see self::limit()
     * @see self::replicate()
     *
     * Note: If casting the result to array, `Itera::valuesOnly()` or `Itera::toArrayMerge()` will be useful,
     * otherwise the overlapping indexes will result in unexpected values (no replication).
     * @see self::valuesOnly()
     */
    public static function loop(iterable $input): iterable
    {
        while (true) {
            yield from $input;
        }
    }

    /**
     * Yields all the elements of the input collection exactly N times.
     *
     *     replicate([1,2,3], 2); // 1,2,3,1,2,3
     *
     * Note: If casting the result to array, `Itera::valuesOnly()` or `Itera::toArrayMerge()` will be useful,
     * otherwise the overlapping indexes will result in unexpected values (no replication).
     * @see self::valuesOnly()
     */
    public static function replicate(iterable $input, int $times): iterable
    {
        $times = max(0, $times);
        while ($times-- > 0) {
            yield from $input;
        }
    }

    /**
     * Returns an array.
     * Preserves keys.
     * Behaves like `array_replace` when overlapping keys occur.
     *
     * WARNING:
     *   If the input is a generator yielding multiple values with the same keys, those values will get overwritten!
     *   The call behaves like `array_replace` for arrays.
     *   This might happen when chaining multiple arrays with numeric keys, for example.
     *   Consider calling `valuesOnly` first: `Itera::toArray(Itera::valuesOnly($input))`.
     * @see self::valuesOnly()
     *
     * See the alternatives:
     * @see self::toArrayMerge() behaves like array_merge
     * @see self::toArrayValues() discards the keys
     */
    public static function toArray(iterable $input): array
    {
        return is_array($input) ? $input : iterator_to_array($input);
    }

    /**
     * Returns an array.
     * Behaves like `array_merge` - it preserves associative keys and discards numeric keys.
     * If associative keys overlap, the former values will be overwritten by the latter ones.
     * @link https://3v4l.org/e1mNY
     */
    public static function toArrayMerge(iterable $input): array
    {
        if (is_array($input)) {
            return $input;
        }
        $output = [];
        foreach ($input as $key => $value) {
            if (
                is_int($key) ||
                is_bool($key) ||                                    // *1
                (is_numeric($key) && (string)(int)$key === $key)    // *2
            ) {
                // append (ignoring the key)
                $output[] = $value;
            } else {
                // (over)write
                $output[$key] = $value;
            }
            // Dev notes:
            // *1/ booleans are cast to integer 0 and 1 when used as array keys; thus we consider them numeric
            // *2/ this double conversion to int and back to string eliminates keys with whitespace and float values in strings;
            //     this emulates what PHP does - keys like "1 " or "2.3" are considered associative and not numeric
        }
        return $output;
    }

    /**
     * Returns an array of values contained in the iterable, discarding all keys (indexes).
     * This mitigates the issue with overlapping indexes.
     */
    public static function toArrayValues(iterable $input): array
    {
        return self::toArray(self::valuesOnly($input));
    }

    /**
     * Returns an iterator.
     * Always returns a new instance of an iterator, even if the input is an iterator already.
     */
    public static function toIterator(iterable $input): Iterator
    {
        return is_array($input) ? new ArrayIterator($input) : new IteratorIterator($input);
    }

    /**
     * Ensures the output is a Traversable object.
     * If the input is such an object already, it is returned as-is.
     */
    public static function ensureTraversable(iterable $input): Traversable
    {
        return is_array($input) ? new ArrayIterator($input) : $input;
    }
}
