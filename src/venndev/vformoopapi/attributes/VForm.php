<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes;

use Attribute;
use AllowDynamicProperties;
use Exception;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_CLASS)]
final class VForm
{

    /**
     * @throws Exception
     */
    public function __construct(
        public string $title,
        public string $type,
        public string $content = ''
    )
    {
        //TODO: Add validation for $type
    }

}