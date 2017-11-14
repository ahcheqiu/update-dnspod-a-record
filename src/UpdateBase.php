<?php

namespace OrzOrc\DDnsUpdate;

use Httpful\Mime;
use Httpful\Request;

abstract class UpdateBase
{
    private $currentIP = '';

    protected $currentIPFile = '';

    protected static $configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config';

    const CURRENT_IP_URL = 'http://freegeoip.net/json/';

    public function __construct($config = [])
    {
        $baseConfigFile = self::$configDir . DIRECTORY_SEPARATOR . 'base.php';
        $baseConfig = require($baseConfigFile);

        $className = get_called_class();
        $instanceConfigFile = lcfirst(substr($className, strrpos($className, '\\') + 1)) . ".php";
        $instanceConfig = require(self::$configDir . DIRECTORY_SEPARATOR . $instanceConfigFile);

        $config = array_merge($baseConfig, $instanceConfig, $config);

        foreach ($config as $key => $value) {
            $fun = 'set' . ucfirst($key);
            if (method_exists($this, $fun)) {
                $this->$fun($value);
            }
        }
    }

    public static function setConfigDir($path)
    {
        self::$configDir = $path;
    }

    public function runUpdate()
    {
        $currentIP = $this->getCurrentIP();
        $previousIP = $this->getRecordIP();
        if ($currentIP == $previousIP) {
            throw new \Exception('IP未改变');
        }

        if ($this->update()) {
            $this->updateRecordIP($currentIP);
        }
    }

    public function getCurrentIP()
    {
        if (empty($this->currentIP)) {
            $response = Request::get(self::CURRENT_IP_URL)
                ->expects(Mime::JSON)
                ->send();

            $ip = '';
            if ($response->hasBody()) {
                $ip = isset($response->body->ip) ? $response->body->ip : '';
            }

            if (empty($ip)) {
                throw new \Exception('没有查询到当前IP');
            }
            $this->currentIP = $ip;
        }
        return $this->currentIP;
    }

    public function getRecordIP()
    {
        if (!file_exists($this->currentIPFile)) {
            return '';
        }

        return file_get_contents($this->currentIPFile);
    }

    public function updateRecordIP($ip)
    {
        if (!file_exists($this->currentIPFile) && !is_writeable(dirname($this->currentIPFile))) {
            throw new \Exception("无法记录当前IP");
        }
        $fp = fopen($this->currentIPFile, "w");
        fwrite($fp, $ip);
        fclose($fp);
        return true;
    }

    abstract public function update();
}
