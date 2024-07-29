<?php

declare(strict_types=1);

namespace venndev\vformoopapi;

use pocketmine\player\Player;
use venndev\vformoopapi\attributes\VForm;
use venndev\vformoopapi\utils\TypeForm;

#[VForm(
    title: "",
    type: TypeForm::NORMAL_FORM,
    content: ""
)]
final class FormSample extends Form
{

    public function __construct(Player $player)
    {
        parent::__construct($player);
    }

}