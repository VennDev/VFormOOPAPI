<?php

declare(strict_types=1);

namespace venndev\vformoopapi\results;

use InvalidArgumentException;

abstract class VResultArray implements VResult
{

    public function __construct(private array $input)
    {
        // TODO: Implement __construct() method.
    }

    public function getInput(): array
    {
        return $this->input;
    }

    public function setInput(mixed $input): void
    {
        if (!is_array($input)) throw new InvalidArgumentException('Input must be a array');
        $this->input = $input;
    }

    abstract public function getResult(): array;

}