<?php

declare(strict_types=1);

namespace venndev\vformoopapi\results;

use InvalidArgumentException;

abstract class VResultBool implements VResult
{

    public function __construct(private bool|string $input)
    {
        // TODO: Implement __construct() method.
    }

    public function getInput(): bool|string
    {
        return $this->input;
    }

    public function setInput(mixed $input): void
    {
        if (!is_bool($input)) throw new InvalidArgumentException('Input must be a boolean');
        $this->input = $input;
    }

    abstract public function getResult(): bool;

}