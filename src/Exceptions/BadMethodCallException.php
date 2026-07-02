<?php

declare(strict_types=1);

namespace Dakujem\Toru\Exceptions;

/**
 * A call to a method that does not exist or is not supported.
 */
final class BadMethodCallException extends \BadMethodCallException implements IndicatesUnintendedToruUsage
{
}
