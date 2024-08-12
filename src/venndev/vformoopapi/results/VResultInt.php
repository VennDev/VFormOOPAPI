<?php

declare(strict_types=1);

namespace venndev\vformoopapi\results;

use InvalidArgumentException;

abstract class VResultInt implements VResult
{

    public function __construct(private int|string $input)
    {
        // TODO: Implement __construct() method.
    }

    public function getInput(): int|string
    {
        return $this->input;
    }

    public function setInput(mixed $input): void
    {
        if (!is_int($input)) throw new InvalidArgumentException('Input must be a integer');
        $this->input = $input;
    }

    abstract public function getResult(): int;

}