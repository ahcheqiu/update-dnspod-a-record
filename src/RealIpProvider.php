<?php

namespace OrzOrc\DDnsUpdate;

interface RealIpProvider
{
    public function get(): string;
}
