<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\results\VResult;
use venndev\vformoopapi\utils\TypeContent;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VDropDown implements IVAttributeForm
{

    public function __construct(
        public VResult|string      $text,
        public VResult|array       $options,
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
            TypeContent::OPTIONS => $this->options,
            TypeContent::DEFAULT => $this->default,
            TypeContent::LABEL => $this->label
        ];
    }

}