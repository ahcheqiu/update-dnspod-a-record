<?php

namespace OrzOrc\DDnsUpdate;

class DomainRecord
{
    private $record;
    private $ip;
    private $remark;
    private $id;
    private $more;

    public function __construct(string $id, string $record, string $ip, string $remark, array $more = [])
    {
        $this->id = $id;
        $this->record = $record;
        $this->ip = $ip;
        $this->remark = $remark;
        $this->more = $more;
    }

    /**
     * @return string
     */
    public function getRecord(): string
    {
        return $this->record;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getRemark(): string
    {
        return $this->remark;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getMore(): array
    {
        return $this->more;
    }

    public function getMoreInfo(string $key): string
    {
        return $this->getMore()[$key] ?? '';
    }
}
