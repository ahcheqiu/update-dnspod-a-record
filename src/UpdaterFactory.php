<?php

namespace OrzOrc\DDnsUpdate;

use OrzOrc\DDnsUpdate\Exception\InvalidUpdaterException;
use OrzOrc\DDnsUpdate\Implementation\FileCachedIp;
use OrzOrc\DDnsUpdate\Implementation\IpApiComRealIpProvider;
use Psr\Container\ContainerInterface;

class UpdaterFactory
{
    /**
     * @var CachedIpProvider
     */
    private $cachedIpProvider = null;
    /**
     * @var RealIpProvider
     */
    private $realIpProvider = null;
    private $container;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param CachedIpProvider $cachedIpProvider
     *
     * @return UpdaterFactory
     */
    public function setCachedIpProvider(CachedIpProvider $cachedIpProvider): UpdaterFactory
    {
        $this->cachedIpProvider = $cachedIpProvider;

        return $this;
    }

    protected function getCachedIpProvider(): CachedIpProvider
    {
        if (is_null($this->cachedIpProvider)) {
            if (is_null($this->container) || !$this->container->has(CachedIpProvider::class)) {
                return new FileCachedIp();
            } else {
                return $this->container->get(CachedIpProvider::class);
            }
        } else {
            return $this->cachedIpProvider;
        }
    }

    /**
     * @param RealIpProvider $realIpProvider
     *
     * @return UpdaterFactory
     */
    public function setRealIpProvider(RealIpProvider $realIpProvider): UpdaterFactory
    {
        $this->realIpProvider = $realIpProvider;

        return $this;
    }

    protected function getRealIpProvider(): RealIpProvider
    {
        if (is_null($this->realIpProvider)) {
            if (is_null($this->container) || !$this->container->has(RealIpProvider::class)) {
                return new IpApiComRealIpProvider();
            } else {
                return $this->container->get(RealIpProvider::class);
            }
        } else {
            return $this->realIpProvider;
        }
    }

    public function get(string $className): Updater
    {
        if (!is_subclass_of($className, Updater::class)) {
            throw new InvalidUpdaterException($className);
        }

        /**
         * @var Updater $updater
         */
        $updater = $this->container->get($className);
        $updater->setCachedIpProvider($this->getCachedIpProvider())
            ->setRealIpProvider($this->getRealIpProvider());

        return $updater;
    }
}
