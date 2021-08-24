<?php
namespace OrzOrc\DDnsUpdate;

use DateTime;
use DateTimeZone;
use Exception;
use Httpful\Exception\ConnectionErrorException;
use Httpful\Request;

class Aliyun extends UpdateBase
{
    private string $accessKey = '';

    private string $accessSecret = '';

    private string $domain = '';

    private string $endPoint = '';

    private string $remark = '';

    /**
     * 具体的更新过程
     *
     * @return int 更新了几条记录
     * @throws Exception
     */
    public function update (): int
    {
        // 如果更新标志为空则中断，以免错误更新
        if(empty($this->getRemark())) {
            throw new Exception('Remark not set');
        }

        $commonData = [
            'Format' => 'JSON',
            'Version' => '2015-01-09',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'AccessKeyId' => $this->getAccessKey(),
            'Timestamp' => $this->getUTCTime()
        ];

        $list = [];

        // 获取列表
        $data = [
            'Action' => 'DescribeDomainRecords',
            'DomainName' => $this->getDomain(),
            'TypeKeyWord' => 'A'
        ];
        try {
            $finalData = array_merge($commonData, $data);
            $finalData['Signature'] = $this->getSignature($finalData, 'GET', $this->getAccessSecret());
            $url = $this->getEndPoint() . '?' . http_build_query($finalData);
            $response = Request::get($url)
                ->send();
            $result = json_decode($response->raw_body, true);
            if($response->hasErrors()) {
                throw new Exception('获取list时有错误');
            }
            $result = $result['DomainRecords']['Record'];
            foreach($result as $item) {
                if(isset($item['Remark']) && $item['Remark'] == $this->getRemark()) {
                    $list[] = $item;
                }
            }
        } catch (ConnectionErrorException $e) {
            throw new Exception("Connection Error When getting record list: " . $e->getMessage());
        }

        if(empty($list)) {
            throw new Exception('没有查询到需要修改的记录');
        }

        // 修改记录
        $data = [
            'Action' => 'UpdateDomainRecord',
            'Value' => $this->getCurrentIP(),
            'Type' => 'A'
        ];
        $count = 0;
        foreach($list as $item) {
            if($item['Value'] != $this->getCurrentIP()) {
                $data['RecordId'] = $item['RecordId'];
                $data['RR'] = $item['RR'];
                $finalData = array_merge($commonData, $data);
                $finalData['Signature'] = $this->getSignature($finalData, 'GET', $this->getAccessSecret());
                $url = $this->getEndPoint() . '?' . http_build_query($finalData);
                $response = Request::get($url)
                    ->send();
                if(!$response->hasErrors()) {
                    $this->addSuccess($finalData['RR']);
                    $count++;
                } else {
                    $this->addFail($finalData['RR']);
                }
            }
        }

        if($count < 1) {
            throw new Exception('没有需要更新的地方');
        }

        return $count;
    }

    /**
     * @throws Exception
     */
    public function getUTCTime(): string
    {
        $timezone = new DateTimeZone('UTC');
        $time = new DateTime('now', $timezone);
        return $time->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * 获取签名
     * @param array $data 数据
     * @param string $method
     * @param string $secret
     * @return string
     */
    public function getSignature(array &$data, string $method, string $secret): string
    {
        // 每次获取新的signatureNonce
        $data['SignatureNonce'] = uniqid();

        // 按顺序排列
        ksort($data);

        // 加密后组合
        $strArray = [];
        foreach($data as $key => $value) {
            $strArray[] = $key . '=' . $this->percentEncode($value);
        }
        $str = implode('&', $strArray);

        // 生成待签名字符串
        $str = $method . '&' . $this->percentEncode('/') . '&' . $this->percentEncode($str);

        // 最终签名
        return base64_encode(hash_hmac('sha1', $str, $secret . '&', true));
    }

    /**
     * 阿里云的加密方式
     * @param string $str 待加密字符串
     * @return string
     */
    public function percentEncode(string $str): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], urlencode($str));
    }

    /**
     * @return string
     */
    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    /**
     * @param string $accessKey
     * @return Aliyun
     */
    public function setAccessKey(string $accessKey): static
    {
        $this->accessKey = $accessKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessSecret(): string
    {
        return $this->accessSecret;
    }

    /**
     * @param string $accessSecret
     * @return Aliyun
     */
    public function setAccessSecret(string $accessSecret): static
    {
        $this->accessSecret = $accessSecret;
        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @return Aliyun
     */
    public function setDomain($domain): static
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return string
     */
    public function getEndPoint(): string
    {
        return $this->endPoint;
    }

    /**
     * @param string $endPoint
     * @return Aliyun
     */
    public function setEndPoint(string $endPoint): static
    {
        $this->endPoint = $endPoint;
        return $this;
    }

    /**
     * @return string
     */
    public function getRemark(): string
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return Aliyun
     */
    public function setRemark(string $remark): static
    {
        $this->remark = $remark;
        return $this;
    }
}
