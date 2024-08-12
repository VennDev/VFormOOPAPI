<?php

declare(strict_types=1);

namespace venndev\vformoopapi\utils;

use InvalidArgumentException;
use venndev\vformoopapi\results\VResultArray;
use venndev\vformoopapi\results\VResultBool;
use venndev\vformoopapi\results\VResultInt;
use venndev\vformoopapi\results\VResultString;

final class ProcessDataInput
{

    public static function processDataVResult(mixed $result): string|array|int|bool|null
    {
        if (
            $result instanceof VResultString ||
            $result instanceof VResultArray ||
            $result instanceof VResultInt ||
            $result instanceof VResultBool
        ) {
            return $result->getResult();
        } elseif (
            is_string($result) ||
            is_array($result) ||
            is_null($result) ||
            is_string($result) ||
            is_int($result) ||
            is_bool($result)
        ) {
            return $result;
        } else {
            throw new InvalidArgumentException('Invalid result type');
        }
    }

}