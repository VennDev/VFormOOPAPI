<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\modal;

use AllowDynamicProperties;
use Attribute;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION|Attribute::TARGET_METHOD)]
final class VButton
{

    public function __construct(public string $text)
    {
        //TODO: Implement constructor
    }

}