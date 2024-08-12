<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\results\VResult;
use venndev\vformoopapi\utils\TypeContent;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VSlider implements IVAttributeForm
{

    public function __construct(
        public VResult|string      $text,
        public VResult|int         $min,
        public VResult|int         $max,
        public VResult|int         $step = -1,
        public VResult|int         $default = -1,
        public VResult|string|null $label = null
    )
    {
        //TODO: Implement constructor
    }

    public function __toArray(): array
    {
        return [
            TypeContent::TEXT => $this->text,
            TypeContent::MIN => $this->min,
            TypeContent::MAX => $this->max,
            TypeContent::STEP => $this->step,
            TypeContent::DEFAULT => $this->default,
            TypeContent::LABEL => $this->label
        ];
    }

}