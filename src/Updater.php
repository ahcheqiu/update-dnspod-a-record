<?php

namespace OrzOrc\DDnsUpdate;

abstract class Updater
{
    public function shouldUpdate(IpCacheInterface $ipCache, RealIpProvider $ipProvider): bool
    {
        $ip = $ipCache->get();
        $realIp = $ipProvider->get();

        return $ip != $realIp;
    }
}
