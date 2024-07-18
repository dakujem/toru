<?php

declare(strict_types=1);

namespace Dakujem\Toru\Exceptions;

use RuntimeException;
use Throwable;

/**
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
final class NoMatchingElementFound extends RuntimeException implements IndicatesExceptionalCase
{
    public iterable $input;
    /** @var callable */
    public $predicate;

    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?? 'No element matching the search criteria found in the collection.', $code ?? 0, $previous);
    }

    public static function fromInputAndPredicate(
        iterable $input,
        callable $predicate,
        ?string $message = null,
        ?int $code = null,
        ?Throwable $previous = null
    ): self {
        $instance = new self($message, $code, $previous);
        $instance->input = $input;
        $instance->predicate = $predicate;
        return $instance;
    }
}
