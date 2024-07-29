<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes;

use Attribute;
use AllowDynamicProperties;
use Exception;
use venndev\vformoopapi\results\VResult;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_CLASS)]
final class VForm
{

    /**
     * @throws Exception
     */
    public function __construct(
        public VResult|string $title,
        public string $type,
        public VResult|string $content = ''
    )
    {
        //TODO: Implement constructor
    }

}