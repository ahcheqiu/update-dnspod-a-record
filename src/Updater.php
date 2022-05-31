<?php

namespace OrzOrc\DDnsUpdate;

use Psr\Log\LoggerInterface;

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
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * 获取当前列表
     * @return DomainRecord[]
     */
    abstract protected function getRecords(): array;

    abstract protected function updateRecord(DomainRecord $record);

    public function shouldUpdate(): bool
    {
        $ip = $this->cacheIpProvider->get();
        $realIp = $this->realIpProvider->get();

        return $ip != $realIp;
    }

    protected function persistIp(string $ip): bool
    {
        return $this->cacheIpProvider->set($ip);
    }

    /**
     * @param string $ip 目标IP
     * @param callable|null $recordFilter 需要接受DomainRecord为参数，返回一个字符串作为被过滤掉的原因，原因为空表示需要更新
     *
     * @return void
     */
    public function update(string $ip, callable $recordFilter = null)
    {
        foreach ($this->getRecords() as $record) {
            // IP正确不需要更新
            if ($record->getIp() == $ip) {
                $this->logger->warning('Record[' . $record->getRecord() . '] will not update due to ip unchanged');
                continue;
            }

            // 自定义过滤器
            if (!is_null($recordFilter)) {
                $filterReason = call_user_func($recordFilter, $record);
                if (!empty($filterReason)) {
                    $this->logger->warning('Record[' . $record->getRecord() . '] will not update due to ' . $filterReason);
                    continue;
                }
            }

            // 执行
            $this->updateRecord(new DomainRecord($record->getId(), $record->getRecord(), $ip, $record->getRemark()));
        }

        // 更新缓存
        $this->persistIp($ip);
    }

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

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
