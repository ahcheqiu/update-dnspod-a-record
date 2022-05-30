<?php

namespace OrzOrc\DDnsUpdate\Implementation;

use OrzOrc\DDnsUpdate\RealIpProvider;

class IpApiComRealIpProvider implements RealIpProvider
{
    private $url = 'http://ip-api.com/json';

    public function get(): string
    {
        // TODO: Implement get() method.
    }
}
