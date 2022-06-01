<?php

namespace OrzOrc\DDnsUpdate\Updater;

use AlibabaCloud\SDK\Alidns\V20150109\Alidns;
use AlibabaCloud\SDK\Alidns\V20150109\Models\DescribeDomainRecordsRequest;
use AlibabaCloud\SDK\Alidns\V20150109\Models\UpdateDomainRecordRequest;
use Darabonba\OpenApi\Models\Config;
use OrzOrc\DDnsUpdate\DomainRecord;
use OrzOrc\DDnsUpdate\Exception\UpdaterFailException;
use OrzOrc\DDnsUpdate\Updater;

class AliyunUpdater extends Updater
{
    private $client;

    public function __construct(array $config)
    {
        $this->client = new Alidns(new Config($config));
    }

    protected function getRecords(): \Generator
    {
        $size = 10;
        $page = 1;
        do {
            try {
                $response = $this->client->describeDomainRecords(new DescribeDomainRecordsRequest([
                    'type' => 'A',
                    'pageNumber' => $page,
                    'pageSize' => $size,
                ]));
            } catch (\Exception $e) {
                $this->logger->error('AliyunUpdater get domain records failed:' . $e->getMessage());
                throw new UpdaterFailException('Aliyun', 'describeDomainRecords', $e);
            }

            $result = $response->body->domainRecords->record;
            foreach ($result as $record) {
                yield new DomainRecord($record->recordId, $record->RR, $record->value, $record->remark);
            }

            // 退出条件
            if (count($result) < $size) {
                break;
            }
        } while (true);
    }

    protected function updateRecord(DomainRecord $record)
    {
        try {
            $this->client->updateDomainRecord(new UpdateDomainRecordRequest([
                'recordId' => $record->getId(),
                'RR' => $record->getRecord(),
                'type' => 'A',
                'value' => $record->getIp(),
            ]));
        } catch (\Exception $e) {
            $this->logger->error('AliyunUpdater update domain records failed:' . $e->getMessage());
            throw new UpdaterFailException('Aliyun', 'updateDomainRecord', $e);
        }
    }
}
