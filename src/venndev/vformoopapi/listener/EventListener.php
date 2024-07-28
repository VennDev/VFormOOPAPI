<?php

declare(strict_types=1);

namespace venndev\vformoopapi\listener;

use pocketmine\entity\Attribute;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as AttributeNetwork;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\player\Player;
use venndev\vformoopapi\VFormLoader;
use vennv\vapm\Async;
use vennv\vapm\FiberManager;
use Throwable;

final class EventListener implements Listener
{

    /**
     * @throws Throwable
     */
    private function processAttribute(Player $player): Async
    {
        return new Async(function () use ($player): void {
            if ($player->getNetworkSession()->isConnected()) {
                $attribute = $player->getAttributeMap()->get(Attribute::EXPERIENCE_LEVEL);
                $id = $attribute->getId();
                $minValue = $attribute->getMinValue();
                $maxValue = $attribute->getMaxValue();
                $value = $attribute->getValue();
                $defaultValue = $attribute->getDefaultValue();
                $networkAttribute = new AttributeNetwork(
                    id: $id,
                    min: $minValue,
                    max: $maxValue,
                    current: $value,
                    default: $defaultValue,
                    modifiers: []
                );
                $updateAttributePacket = UpdateAttributesPacket::create(
                    actorRuntimeId: $player->getId(),
                    entries: [$networkAttribute],
                    tick: 0
                );
                $player->getNetworkSession()->sendDataPacket($updateAttributePacket);
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        new Async(function () use ($event): void {
            $packets = $event->getPackets();
            $targets = $event->getTargets();
            foreach ($packets as $packet) {
                foreach ($targets as $target) {
                    if ($packet instanceof ModalFormRequestPacket) {
                        $player = $target->getPlayer();
                        if ($player !== null && $player->isOnline()) {
                            // Async Await to handle too many packets being sent at one time.
                            for ($i = 0; $i < VFormLoader::getPacketsToSend(); ++$i) {
                                Async::await($this->processAttribute($player));
                            }
                        }
                        // Confirm the correct packet delivery to the player who needs it.
                        break;
                    }
                    FiberManager::wait();
                }
                FiberManager::wait();
            }
        });
    }

}