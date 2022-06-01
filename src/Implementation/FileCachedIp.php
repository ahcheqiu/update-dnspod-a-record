<?php

namespace OrzOrc\DDnsUpdate\Implementation;

use OrzOrc\DDnsUpdate\CachedIpProvider;
use OrzOrc\DDnsUpdate\Exception\PermissionDeniedException;

class FileCachedIp implements CachedIpProvider
{
    private $cacheFile;

    public function __construct(string $cacheFilePath = '')
    {
        if (empty($cacheFilePath)) {
            $cacheFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ddns_updater_cache.ip';
        }
        $this->cacheFile = $cacheFilePath;
    }

    public function get(): string
    {
        $this->touch();

        return file_get_contents($this->cacheFile);
    }

    public function set(string $ip): bool
    {
        $this->touch();

        return file_put_contents($this->cacheFile, $ip);
    }

    private function touch()
    {
        if (file_exists($this->cacheFile) && is_writable($this->cacheFile) && is_readable($this->cacheFile)) {
            return;
        }

        // 确定文件所在的文件夹存在
        $dir = dirname($this->cacheFile);
        if (!file_exists($dir)) {
            if(!@mkdir($dir, 0775, true)) {
                throw new PermissionDeniedException('file can not be created under its dir');
            }
        }

        // 确定该文件存在
        if (!@touch($this->cacheFile)) {
            throw new PermissionDeniedException('file can not be created');
        }

        // 确定有权限
        if (!is_writable($this->cacheFile) || !is_readable($this->cacheFile)) {
            if (!@chmod($this->cacheFile, 0777)) {
                throw new PermissionDeniedException('file can not be read or written');
            }
        }
    }
}
