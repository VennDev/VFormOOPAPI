# VFormOOPAPI
- The Form design library for the server running PocketMine-PMMP5 is object-oriented.
- The library contains asynchronous tasks that help download form data to the player without having to wait too long, resulting in a server stop at a certain second!

# Example code be-like
```php
<?php

declare(strict_types=1);

namespace myplugin\forms;

use pocketmine\player\Player;
use venndev\vformoopapi\Form;
use venndev\vformoopapi\attributes\normal\VButton;
use venndev\vformoopapi\attributes\VForm;
use venndev\vformoopapi\utils\TypeForm;

#[VForm(
    title: "This is name form",
    type: TypeForm::NORMAL_FORM,
    content: ""
)]
final class TestForm extends Form
{

    public function __construct(Player $player)
    {
        parent::__construct($player);
    }

    #[VButton(
        text: "Test Button Have Image",
        image: "https://example.com/image.png"
    )]
    public function testButton(Player $player, mixed $data): void
    {
        $player->sendMessage("You clicked the button image!");
    }

    #[VButton(
        text: "Test Button No Image"
    )]
    public function testButton(Player $player, mixed $data): void
    {
        $player->sendMessage("You clicked the test button no image!");
    }

    public function onClose(Player $player): void
    {
        $player->sendMessage("You have closed the form");
    }

}
```
# PHP Required
- Version >= `8.2`

# Virion Required
- [LibVapmPMMP](https://github.com/VennDev/LibVapmPMMP)

# Example plugins
- [PluginExample](https://github.com/VennDev/TestVForm)
