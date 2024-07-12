<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VDropDown
{

    public function __construct(
        public string  $text,
        public array   $options,
        public int     $default = -1,
        public ?string $label = null
    )
    {

    }

}