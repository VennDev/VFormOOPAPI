<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\results\VResult;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VStepSlider implements IVAttributeForm
{

    public function __construct(
        public VResult|string $text,
        public VResult|array  $steps,
        public int            $default = -1,
        public ?string        $label = null
    )
    {
        //TODO: Implement constructor
    }

}