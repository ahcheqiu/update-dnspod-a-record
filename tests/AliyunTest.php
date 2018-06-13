<?php
namespace OrzOrc\DDnsUpdate\Tests;

use OrzOrc\DDnsUpdate\Aliyun;

class AliyunTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Aliyun
     */
    private $instance = null;

    public function setUp() {
        $this->instance = new Aliyun();
    }

    public function testPercentEncode() {
        $this->assertEquals('%20%2A~%2F', $this->instance->percentEncode(' *~/'));
    }

    public function testGetSignature() {
        $data = [
            'Format' => 'XML',
            'AccessKeyId' => 'testid',
            'Action' => 'DescribeDomainRecords',
            'SignatureMethod' => 'HMAC-SHA1',
            'DomainName' => 'example.com',
            'SignatureNonce' => 'f59ed6a9-83fc-473b-9cc6-99c95df3856e',
            'SignatureVersion' => '1.0',
            'Version' => '2015-01-09',
            'Timestamp' => '2016-03-24T16:41:54Z'
        ];
        $this->assertEquals('uRpHwaSEt3J+6KQD//svCh/x+pI=', $this->instance->getSignature($data, 'GET', 'testsecret'));
    }
}
