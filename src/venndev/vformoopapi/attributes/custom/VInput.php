<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VInput
{

    public function __construct(
        public string  $text,
        public string  $placeholder = "",
        public ?string $default = null,
        public ?string $label = null
    )
    {
        //TODO: Implement constructor
    }

}