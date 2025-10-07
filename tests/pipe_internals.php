<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;
use Dakujem\Toru\IteraFn;
use Tester\Assert;


$sequence = range(51,100);
$array = $sequence
        |> IteraFn::filter(fn($i) => 0 == $i % 2) // even numbers only
        |> IteraFn::reindex(fn($i) => $i)
        |> IteraFn::limit(10)
        |> IteraFn::toArray();

Assert::same([
    52 => 52,
    54 => 54,
    56 => 56,
    58 => 58,
    60 => 60,
    62 => 62,
    64 => 64,
    66 => 66,
    68 => 68,
    70 => 70,
], $array);
