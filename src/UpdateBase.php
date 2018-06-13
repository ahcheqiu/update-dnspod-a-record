<?php

namespace OrzOrc\DDnsUpdate;

use Httpful\Mime;
use Httpful\Request;

abstract class UpdateBase
{
    private $currentIP = '';

    protected $currentIPFile = '';

    const CURRENT_IP_URL = 'http://freegeoip.net/json/';

    /**
     * UpdateBase constructor.
     *
     * @param string $configDir
     */
    public function __construct($configDir = '')
    {
        if(empty($configDir)) {
            $configDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';
        }

        // base config
        $baseConfigFile = $configDir . DIRECTORY_SEPARATOR . 'base.php';
        $baseConfig = require($baseConfigFile);

        // instance specific config
        $className = get_called_class();
        $instanceConfigFile = lcfirst(substr($className, strrpos($className, '\\') + 1)) . ".php";
        $instanceConfig = require($configDir . DIRECTORY_SEPARATOR . $instanceConfigFile);

        // instance config override base config
        $config = array_merge($baseConfig, $instanceConfig);
        foreach ($config as $key => $value) {
            $fun = 'set' . ucfirst($key);
            if (method_exists($this, $fun)) {
                $this->$fun($value);
            }
        }
    }

    /**
     * 运行更新
     *
     * @throws \Exception
     */
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

    /**
     * 获取当前本机的外网IP
     *
     * @return string
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
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

    /**
     * 返回上一次记录的DNS服务器的IP
     *
     * @return bool|string
     */
    public function getRecordIP()
    {
        if (!file_exists($this->currentIPFile)) {
            return '';
        }

        return file_get_contents($this->currentIPFile);
    }

    /**
     * 将DNS服务器的IP记录为指定IP
     *
     * @param string $ip
     *
     * @return bool
     * @throws \Exception
     */
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

    /**
     * 具体的更新过程
     *
     * @return int 更新了几条记录
     */
    abstract public function update();
}
