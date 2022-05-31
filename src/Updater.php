<?php

namespace OrzOrc\DDnsUpdate;

abstract class Updater
{
    /**
     * @var CachedIpProvider
     */
    private $cacheIpProvider;
    /**
     * @var RealIpProvider
     */
    private $realIpProvider;

    public function shouldUpdate(): bool
    {
        $ip = $this->cacheIpProvider->get();
        $realIp = $this->realIpProvider->get();

        return $ip != $realIp;
    }

    public function persistIp(string $ip): bool
    {
        return $this->cacheIpProvider->set($ip);
    }

    abstract public function updateTo(string $ip): bool;

    public function setCachedIpProvider(CachedIpProvider $cacheProvider): self
    {
        $this->cacheIpProvider = $cacheProvider;

        return $this;
    }

    public function setRealIpProvider(RealIpProvider $ipProvider): self
    {
        $this->realIpProvider = $ipProvider;

        return $this;
    }
}
