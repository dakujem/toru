<?php

declare(strict_types=1);

namespace Dakujem\Toru;

/**
 * A trivial generic processing pipeline implementation.
 *
 * The input is processed by the first stage, the result is passed on to the second and so on.
 * Each stage receives the output of the previous stage.
 *
 * Note:
 *   Since PHP 8.5 it is better to use the native pipe operator instead.
 */
final class Pipeline
{
    public static function through(mixed $passable, callable ...$stages): mixed
    {
        foreach ($stages as $stage) {
            $passable = $stage($passable);
        }
        return $passable;
    }

    public static function throughStages(mixed $passable, iterable $stages): mixed
    {
        return self::through($passable, ...$stages);
    }
}
