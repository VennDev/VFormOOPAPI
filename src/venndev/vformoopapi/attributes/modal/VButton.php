<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\modal;

use AllowDynamicProperties;
use Attribute;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\results\VResult;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION|Attribute::TARGET_METHOD)]
final class VButton implements IVAttributeForm
{

    public function __construct(public VResult|string $text)
    {
        //TODO: Implement constructor
    }

}