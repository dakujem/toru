<?php

declare(strict_types=1);

namespace Dakujem\Toru\Exceptions;

/**
 * A callable provided a value that is out of range or of invalid type.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class UnexpectedValueException extends \UnexpectedValueException implements IndicatesUnintendedToruUsage
{
}
