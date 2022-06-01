<?php

namespace OrzOrc\DDnsUpdate;

interface RealIpProvider
{
    /**
     * @return string
     * @throws \Throwable
     */
    public function get(): string;
}
