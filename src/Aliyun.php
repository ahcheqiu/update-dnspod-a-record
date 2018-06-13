<?php
namespace OrzOrc\DDnsUpdate;

class Aliyun extends UpdateBase
{
    /**
     * 具体的更新过程
     *
     * @return int 更新了几条记录
     */
    public function update ()
    {
        $data = [
            'Format' => 'JSON',
            'Version' => '2015-01-09'
        ];
    }
}
