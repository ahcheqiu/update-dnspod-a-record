<?php

namespace OrzOrc\DDnsUpdate;

use Httpful\Mime;
use Httpful\Request;

abstract class UpdateBase
{
    private $currentIP = '';

    private $status = [
        'success' => [],
        'fail' => []
    ];

    protected $currentIPFile = '';

    const CURRENT_IP_URL = 'http://ip-api.com/json';

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
        if(file_exists($baseConfigFile)) {
            $baseConfig = require($baseConfigFile);
        } else {
            $baseConfig = [];
        }

        // instance specific config
        $className = get_called_class();
        $instanceConfigFile = $configDir . DIRECTORY_SEPARATOR . lcfirst(substr($className, strrpos($className, '\\') + 1)) . ".php";
        if(file_exists($instanceConfigFile)) {
            $instanceConfig = require($instanceConfigFile);
        } else {
            $instanceConfig = [];
        }

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

        $count = $this->update();
        if ($count) {
            $this->updateRecordIP($currentIP);
        }
        return $count;
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
                $ip = isset($response->body->query) ? $response->body->query : '';
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

    /**
     * @return string
     */
    public function getCurrentIPFile()
    {
        return $this->currentIPFile;
    }

    /**
     * @param string $currentIPFile
     * @return UpdateBase
     */
    public function setCurrentIPFile($currentIPFile)
    {
        $this->currentIPFile = $currentIPFile;
        return $this;
    }

    public function addSuccess($domain) {
        $this->status['success'][] = $domain;
        return $this;
    }

    public function addFail($domain) {
        $this->status['fail'][] = $domain;
        return $this;
    }

    public function getSuccessCount() {
        return count($this->status['success']);
    }

    public function getFailCount() {
        return count($this->status['fail']);
    }

    public function getCompleteCount() {
        return $this->getSuccessCount() + $this->getFailCount();
    }

    public function getCompleteStatus() {
        return $this->status;
    }

    public function getSuccessStatus() {
        return array_unique($this->status['success']);
    }

    public function getFailStatus() {
        return array_unique($this->status['fail']);
    }
}
