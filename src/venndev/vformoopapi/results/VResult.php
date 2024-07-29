<?php

declare(strict_types=1);

namespace venndev\vformoopapi\results;

interface VResult
{

    public function getInput(): mixed;

    public function setInput(mixed $input);

    public function getResult(): mixed;

}