<?php

declare(strict_types=1);

namespace Dakujem\Toru;

/**
 * TODO Flow or Carry? Or... Pipeline? Chain? Trans? Trance?
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class Carry
{
    public function __construct(
        private readonly mixed $current,
    ) {
    }

    public static function with(mixed $current): self
    {
        return new self($current);
    }

    public function do(callable $fn): self
    {
        return new self(
            $fn($this->current),
        );
    }

    public function result(): mixed
    {
        return $this->current;
    }
}
