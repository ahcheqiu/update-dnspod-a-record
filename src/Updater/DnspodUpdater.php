<?php

namespace OrzOrc\DDnsUpdate\Updater;

use OrzOrc\DDnsUpdate\DomainRecord;
use OrzOrc\DDnsUpdate\Exception\UpdaterFailException;
use OrzOrc\DDnsUpdate\Updater;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Dnspod\V20210323\DnspodClient;
use TencentCloud\Dnspod\V20210323\Models\DescribeDomainListRequest;
use TencentCloud\Dnspod\V20210323\Models\DescribeRecordListRequest;
use TencentCloud\Dnspod\V20210323\Models\ModifyRecordRequest;

class DnspodUpdater extends Updater
{
    private $client;

    public function __construct(array $config)
    {
        [
            'SecretId' => $secretId,
            'SecretKey' => $secretKey,
            'Endpoint' => $endpoint,
        ] = $config;
        $cred = new Credential($secretId, $secretKey);
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint($endpoint);
        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $this->client = new DnspodClient($cred, "", $clientProfile);
    }

    protected function getRecords(): \Generator
    {
        foreach ($this->getDomains() as $domain) {
            $request = new DescribeRecordListRequest();
            $request->setRecordType('A');
            $request->setDomain($domain);
            $size = 10;
            $offset = 0;

            do {
                $request->setOffset($offset);
                $request->setLimit($size);

                try {
                    $response = $this->client->DescribeRecordList($request);
                } catch (\Exception $e) {
                    $this->logger->error('DnspodUpdater get domain records failed:' . $e->getMessage());
                    throw new UpdaterFailException('Dnspod', 'DescribeRecordList', $e);
                }
                $list = $response->getRecordList();
                foreach ($list as $record) {
                    $moreData = [
                        'Line' => $record['Line'],
                        'Domain' => $domain,
                    ];
                    yield new DomainRecord($record['RecordId'], $record['Name'], $record['Value'], $record['Remark'], $moreData);
                }

                if (count($list) < $size) {
                    break;
                }
                $offset += $size;
            } while (true);
        }
    }

    protected function updateRecord(DomainRecord $record)
    {
        $request = new ModifyRecordRequest();
        $request->setDomain($record->getMoreInfo('Domain'));
        $request->setRecordType('A');
        $request->setRecordLine($record->getMoreInfo('Line'));
        $request->setValue($record->getIp());
        $request->setRecordId($record->getId());

        try {
            $this->client->ModifyRecord($request);
        } catch (\Exception $e) {
            $this->logger->error('DnspodUpdater update domain records failed:' . $e->getMessage());
            throw new UpdaterFailException('Dnspod', 'ModifyRecord', $e);
        }
    }

    private function getDomains(): \Generator
    {
        $size = 10;
        $offset = 0;
        do {
            $request = new DescribeDomainListRequest();
            $request->setLimit($size);
            $request->setOffset($offset);

            try {
                $response = $this->client->DescribeDomainList($request);
            } catch (\Exception $e) {
                $this->logger->error('DnspodUpdater get domains failed:' . $e->getMessage());
                throw new UpdaterFailException('Dnspod', 'DescribeDomainListRequest', $e);
            }
            $list = $response->getDomainList();
            foreach ($list as $domain) {
                yield $domain['NAME'];
            }

            if (count($list) < $size) {
                break;
            }
            $offset += $size;
        } while (true);
    }
}
