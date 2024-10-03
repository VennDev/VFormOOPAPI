<?php

declare(strict_types=1);

namespace venndev\vformoopapi;

use pocketmine\plugin\PluginBase;
use vennv\vapm\VapmPMMP;

final class VFormLoader
{

    private static int $packetsToSend = 7;

    private static bool $isInit = false;

    public static function init(PluginBase $plugin): void
    {
        if (self::$isInit) return;
        self::$isInit = true;
        VapmPMMP::init($plugin);
        //$plugin->getServer()->getPluginManager()->registerEvents(new listener\EventListener(), $plugin);
    }

    public static function getPacketsToSend(): int
    {
        return self::$packetsToSend;
    }

    public static function setPacketsToSend(int $packetsToSend): void
    {
        self::$packetsToSend = $packetsToSend;
    }

    public static function isInit(): bool
    {
        return self::$isInit;
    }
}

