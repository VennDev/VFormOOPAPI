<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VToggle
{

    public function __construct(
        public string  $text,
        public bool    $default = false,
        public ?string $label = null
    )
    {
        //TODO: Implement constructor
    }

}