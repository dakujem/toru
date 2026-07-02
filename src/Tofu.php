<?php

declare(strict_types=1);

namespace Dakujem\Toru;

use Dakujem\Toru\Exceptions\BadMethodCallException;

/**
 * Static factory for partially applied variants of iteration primitives and utilities.
 *
 * The factory methods create partially applied callable equivalents of the `Itera` class methods
 * having the same functionality as their counterparts in the `Itera` class,
 * with the input collection being the only free parameter, fixing the rest.
 * All the returned callables accept a single parameter of iterable type (the input collection).
 * @see Itera
 */
class Tofu
{
    //
    // The following methods return a callable that decorates the input iterable,
    // returning a new iterable object (a Generator in most cases).
    //

    /**
     * @see Itera::chain()
     */
    public static function chain(iterable ...$more): callable
    {
        return fn(iterable $input): iterable => Itera::chain($input, ...$more);
    }

    /**
     * Alias for `chain`.
     * @see Itera::chain()
     */
    public static function append(iterable ...$more): callable
    {
        return self::chain(...$more);
    }

    /**
     * @see Itera::adjust()
     */
    public static function adjust(?callable $values = null, ?callable $keys = null): callable
    {
        return fn(iterable $input): iterable => Itera::adjust($input, $values, $keys);
    }

    /**
     * @see Itera::apply()
     */
    public static function apply(callable $values): callable
    {
        return fn(iterable $input): iterable => Itera::apply($input, $values);
    }

    /**
     * Alias for `apply`.
     * @see Itera::map()
     */
    public static function map(callable $values): callable
    {
        return fn(iterable $input): iterable => Itera::map($input, $values);
    }

    /**
     * @see Itera::reindex()
     */
    public static function reindex(callable $keys): callable
    {
        return fn(iterable $input): iterable => Itera::reindex($input, $keys);
    }

    /**
     * @see Itera::filter()
     */
    public static function filter(callable $predicate): callable
    {
        return fn(iterable $input): iterable => Itera::filter($input, $predicate);
    }

    /**
     * @see Itera::limit()
     */
    public static function limit(int $limit): callable
    {
        return fn(iterable $input): iterable => Itera::limit($input, $limit);
    }

    /**
     * @see Itera::omit()
     */
    public static function omit(int $count): callable
    {
        return fn(iterable $input): iterable => Itera::omit($input, $count);
    }

    /**
     * @see Itera::slice()
     */
    public static function slice(int $offset, int $limit): callable
    {
        return fn(iterable $input): iterable => Itera::slice($input, $offset, $limit);
    }

    /**
     * @see Itera::tap()
     */
    public static function tap(callable $effect): callable
    {
        return fn(iterable $input): iterable => Itera::tap($input, $effect);
    }

    /**
     * Alias for `tap`.
     * @see Itera::each()
     */
    public static function each(callable $effect): callable
    {
        return fn(iterable $input): iterable => Itera::each($input, $effect);
    }

    /**
     * @see Itera::unfold()
     */
    public static function unfold(callable $mapper): callable
    {
        return fn(iterable $input): iterable => Itera::unfold($input, $mapper);
    }

    /**
     * @see Itera::valuesOnly()
     */
    public static function valuesOnly(): callable
    {
        return fn(iterable $input): iterable => Itera::valuesOnly($input);
    }

    /**
     * @see Itera::keysOnly()
     */
    public static function keysOnly(): callable
    {
        return fn(iterable $input): iterable => Itera::keysOnly($input);
    }

    /**
     * @see Itera::flip()
     */
    public static function flip(): callable
    {
        return fn(iterable $input): iterable => Itera::flip($input);
    }

    /**
     * @see Itera::loop()
     */
    public static function loop(): callable
    {
        return fn(iterable $input): iterable => Itera::loop($input);
    }

    /**
     * @see Itera::replicate()
     */
    public static function replicate(int $times): callable
    {
        return fn(iterable $input): iterable => Itera::replicate($input, $times);
    }

    //
    // The following method returns a callable that produces an iterable from a mixed type value.
    //

    /**
     * @see Itera::repeat()
     */
    public static function repeat(): callable
    {
        return fn(mixed $input): iterable => Itera::repeat($input);
    }

    //
    // The following methods return a callable that immediately iterates the collection
    // and evaluates all decorators, returning a `mixed` value type.
    //

    /**
     * @see Itera::toArray()
     */
    public static function toArray(): callable
    {
        return fn(iterable $input): mixed => Itera::toArray($input);
    }

    /**
     * @see Itera::toArrayValues()
     */
    public static function toArrayValues(): callable
    {
        return fn(iterable $input): mixed => Itera::toArrayValues($input);
    }

    /**
     * @see Itera::toArrayMerge()
     */
    public static function toArrayMerge(): callable
    {
        return fn(iterable $input): mixed => Itera::toArrayMerge($input);
    }

    /**
     * @see Itera::toIterator()
     */
    public static function toIterator(): callable
    {
        return fn(iterable $input): mixed => Itera::toIterator($input);
    }

    /**
     * @see Itera::ensureTraversable()
     */
    public static function ensureTraversable(): callable
    {
        return fn(iterable $input): mixed => Itera::ensureTraversable($input);
    }

    /**
     * @see Itera::count()
     */
    public static function count(): callable
    {
        return fn(iterable $input): mixed => Itera::count($input);
    }

    /**
     * @see Itera::search()
     */
    public static function search(callable $predicate, mixed $default = null): callable
    {
        return fn(iterable $input): mixed => Itera::search($input, $predicate, $default);
    }

    /**
     * @see Itera::searchOrFail()
     */
    public static function searchOrFail(callable $predicate): callable
    {
        return fn(iterable $input): mixed => Itera::searchOrFail($input, $predicate);
    }

    /**
     * @see Itera::firstValue()
     */
    public static function firstValue(): callable
    {
        return fn(iterable $input): mixed => Itera::firstValue($input);
    }

    /**
     * @see Itera::firstKey()
     */
    public static function firstKey(): callable
    {
        return fn(iterable $input): mixed => Itera::firstKey($input);
    }

    /**
     * @see Itera::firstValueOrDefault()
     */
    public static function firstValueOrDefault(mixed $default = null): callable
    {
        return fn(iterable $input): mixed => Itera::firstValueOrDefault($input, $default);
    }

    /**
     * @see Itera::firstKeyOrDefault()
     */
    public static function firstKeyOrDefault(mixed $default = null): callable
    {
        return fn(iterable $input): mixed => Itera::firstKeyOrDefault($input, $default);
    }

    /**
     * @see Itera::reduce()
     */
    public static function reduce(callable $reducer, mixed $initial = null): callable
    {
        return fn(iterable $input): mixed => Itera::reduce($input, $reducer, $initial);
    }

    /**
     * This class may be extended to add new methods.
     * Any of the methods forwarding to the `Itera` class
     * may be overridden in the extending class to change the default behaviour.
     *
     * Calls to unsupported methods are routed here to produce a helpful hint.
     */
    public static function __callStatic(string $name, array $arguments): callable
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
            return 'The method is not supported in partially applied form.';
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

        return null;
    }
}
