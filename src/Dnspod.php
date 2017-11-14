<?php

namespace OrzOrc\DDnsUpdate;

use Httpful\Mime;
use Httpful\Request;

class Dnspod extends UpdateBase
{
    private $token = '';

    private $domain = '';

    private $listURL = '';

    private $updateURL = '';

    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    public function setListURL($url)
    {
        $this->listURL = $url;
        return $this;
    }

    public function setUpdateURL($url)
    {
        $this->updateURL = $url;
        return $this;
    }

    public function update()
    {
        $data = array(
            'login_token' => $this->token,
            'format' => 'json',
            'domain' => $this->domain
        );
        // 获取全部的记录
        $response = Request::post('https://dnsapi.cn/Record.List')
            ->body(http_build_query($data))
            ->contentType(Mime::FORM)
            ->send();

        if (!$response->hasBody()) {
            throw new \Exception('没有查到DNS记录');
        }

        $data = json_decode($response->body, true);
        if ($data['status']['code'] != 1) {
            throw new \Exception('DNS记录获取错误');
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
        $response = Request::post('https://dnsapi.cn/Batch.Record.Modify')
            ->body(http_build_query($data))
            ->contentType(Mime::FORM)
            ->send();
        if (!$response->hasBody()) {
            throw new \Exception('更新记录出错');
        }

        $data = json_decode($response->body, true);
        if ($data['status']['code'] != 1) {
            throw new \Exception('没有更新成功');
        }
        return count($change);
    }
}
