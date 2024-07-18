<?php

declare(strict_types=1);

namespace Dakujem\Toru\Exceptions;

use RuntimeException;
use Throwable;

/**
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
final class EmptyCollectionException extends RuntimeException implements IndicatesExceptionalCase
{
    public iterable $input;

    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?? 'Empty input collection encountered.', $code ?? 0, $previous);
    }

    public static function fromInput(
        iterable $input,
        ?string $message = null,
        ?int $code = null,
        ?Throwable $previous = null
    ): self {
        $instance = new self($message, $code, $previous);
        $instance->input = $input;
        return $instance;
    }
}
