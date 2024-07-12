<?php

declare(strict_types=1);

namespace venndev\vformoopapi\attributes\normal;

use AllowDynamicProperties;
use Attribute;
use venndev\vformoopapi\utils\ImageType;
use venndev\vformoopapi\utils\UrlUtil;

#[AllowDynamicProperties] #[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class VButton
{

    public ?string $type = null;

    public function __construct(
        public string  $text,
        public string  $image = '',
        public ?string $label = null
    )
    {
        UrlUtil::isUrl($this->image) ? $this->type = ImageType::URL : $this->type = ImageType::PATH;
    }

}