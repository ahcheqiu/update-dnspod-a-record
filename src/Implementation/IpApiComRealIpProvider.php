<?php

namespace OrzOrc\DDnsUpdate\Implementation;

use GuzzleHttp\Client;
use OrzOrc\DDnsUpdate\RealIpProvider;

class IpApiComRealIpProvider implements RealIpProvider
{
    private $url = 'http://ip-api.com/json';

    /**
     * @throws \Throwable
     */
    public function get(): string
    {
        $client = new Client();
        $response = $client->get($this->url);
        if ($response->getStatusCode() == 200) {
            $body = strval($response->getBody());
            $result = json_decode($body, true);
            if (is_array($result) && isset($result['query'])) {
                return $result['query'];
            } else {
                throw new \Exception('ip-api.com get real ip failed ret=' . $body);
            }
        } else {
            throw new \Exception('ip-api.com get real ip failed ret=' . $response->getBody());
        }
    }
}
