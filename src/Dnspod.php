<?php

namespace OrzOrc\DDnsUpdate;

use Exception;
use Httpful\Exception\ConnectionErrorException;
use Httpful\Mime;
use Httpful\Request;

class Dnspod extends UpdateBase
{
    private $token = '';

    private $domain = '';

    private $listURL = '';

    private $updateURL = '';

    public function setToken($token): Dnspod
    {
        $this->token = $token;
        return $this;
    }

    public function setDomain($domain): Dnspod
    {
        $this->domain = $domain;
        return $this;
    }

    public function setListURL($url): Dnspod
    {
        $this->listURL = $url;
        return $this;
    }

    public function setUpdateURL($url): Dnspod
    {
        $this->updateURL = $url;
        return $this;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function update(): int
    {
        $data = array(
            'login_token' => $this->token,
            'format' => 'json',
            'domain' => $this->domain
        );
        // 获取全部的记录
        try {
            $response = Request::post($this->listURL)
                ->body(http_build_query($data))
                ->contentType(Mime::FORM)
                ->send();
        } catch (ConnectionErrorException $e) {
            throw new Exception("Connection Error When getting record list: " . $e->getMessage());
        }

        if (!$response->hasBody()) {
            throw new Exception('没有查到DNS记录');
        }

        $data = json_decode($response->body, true);
        if ($data['status']['code'] != 1) {
            throw new Exception('DNS记录获取错误');
        }

        //记录需要变更的A记录ID
        $change = array();
        foreach ($data['records'] as $record) {
            if ($record['type'] == 'A' && $record['value'] != $this->getCurrentIP()) {
                $change[] = $record['id'];
            }
        }
        if (empty($change)) {
            return 0;
        }

        //实际变更操作
        $data = array(
            'login_token' => $this->token,
            'format' => 'json',
            'record_id' => implode(',', $change),
            'change' => 'value',
            'change_to' => $this->getCurrentIP()
        );
        try{
            $response = Request::post($this->updateURL)
                ->body(http_build_query($data))
                ->contentType(Mime::FORM)
                ->send();
        } catch (ConnectionErrorException $e) {
            throw new Exception("Connection Error When getting update record: " . $e->getMessage());
        }
        if (!$response->hasBody()) {
            throw new Exception('更新记录出错');
        }

        $data = json_decode($response->body, true);
        if ($data['status']['code'] != 1) {
            throw new Exception('没有更新成功');
        }
        return count($change);
    }
}
