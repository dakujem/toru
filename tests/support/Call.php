<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Represents a call to a method supported by Itera/Dash/IteraFn classes.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class Call
{
    private string $method;
    private array $args;

    public function __construct(
        string $method,
        mixed ...$args,
    ) {
        $this->method = $method;
        $this->args = $args;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function args(): array
    {
        return $this->args;
    }
}
