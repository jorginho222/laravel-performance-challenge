<?php

namespace Src\Shared\Domain;

use InvalidArgumentException;

/**
 * An amount with two decimals, kept in cents to avoid floating point errors.
 */
final readonly class Money
{
    private function __construct(public int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * @param  string  $amount  a decimal amount such as "19.99", "5.5" or "3"
     */
    public static function fromDecimal(string $amount): self
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException("Invalid amount: {$amount}.");
        }

        return new self((int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '0', 2, '0'));
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function multiply(int $times): self
    {
        return new self($this->cents * $times);
    }

    public function toDecimal(): string
    {
        return sprintf('%d.%02d', intdiv($this->cents, 100), $this->cents % 100);
    }
}
