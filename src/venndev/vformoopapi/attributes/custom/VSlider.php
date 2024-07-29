<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\results\VResult;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VSlider implements IVAttributeForm
{

    public function __construct(
        public VResult|string $text,
        public int            $min,
        public int            $max,
        public int            $step = -1,
        public int            $default = -1,
        public ?string        $label = null
    )
    {
        //TODO: Implement constructor
    }

}