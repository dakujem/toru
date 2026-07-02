<?php

declare(strict_types=1);

namespace Dakujem\Toru;

/**
 * @deprecated Use the `Tofu` class instead. This alias will be removed in v3.
 *             This class has been renamed because the introduction of the pipe operator in PHP 8.5
 *             has made it more useful and less of a niche - and the "IteraFn" name feels awkward.
 */
class IteraFn extends Tofu
{
}
