<?php

declare(strict_types=1);

namespace venndev\vformoopapi\results;

use InvalidArgumentException;

abstract class VResultString implements VResult
{

    public function __construct(private string $input)
    {
        // TODO: Implement __construct() method.
    }

    public function getInput(): string
    {
        return $this->input;
    }

    public function setInput(mixed $input): void
    {
        if (!is_string($input)) throw new InvalidArgumentException('Input must be a string');
        $this->input = $input;
    }

    abstract public function getResult(): string;

}