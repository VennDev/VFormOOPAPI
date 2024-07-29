<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\custom;

use AllowDynamicProperties;
use Attribute;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\results\VResult;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VInput implements IVAttributeForm
{

    public function __construct(
        public VResult|string      $text,
        public VResult|string      $placeholder = "",
        public null|VResult|string $default = null,
        public ?string             $label = null
    )
    {
        //TODO: Implement constructor
    }

}