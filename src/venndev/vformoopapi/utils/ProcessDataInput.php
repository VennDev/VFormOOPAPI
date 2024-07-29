<?php

declare(strict_types=1);

namespace venndev\vformoopapi\utils;

use InvalidArgumentException;
use venndev\vformoopapi\results\VResultArray;
use venndev\vformoopapi\results\VResultString;

final class ProcessDataInput
{

    public static function processDataVResult(mixed $result): string|array|null
    {
        if (
            $result instanceof VResultString ||
            $result instanceof VResultArray
        ) {
            return $result->getResult();
        } elseif (is_string($result)) {
            return $result;
        } elseif (is_array($result)) {
            return $result;
        } elseif (is_null($result)) {
            return null;
        } else {
            throw new InvalidArgumentException('Invalid result type');
        }
    }

}