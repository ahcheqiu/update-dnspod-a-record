<?php

namespace OrzOrc\DDnsUpdate;

interface CachedIpProvider
{
    public function get(): string;

    public function set(string $ip): bool;
}
