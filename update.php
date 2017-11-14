<?php
require_once 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$response = \Httpful\Request::get('http://freegeoip.net/json/')
    ->expects(\Httpful\Mime::JSON)
    ->send();

$ip = '';
if($response->hasBody()) {
    $ip = isset($response->body->ip) ? $response->body->ip : '';
}

register_shutdown_function(function(){
    echo "\n";
});

$ipFile = 'current.ip';
$loginToken = '32208,42a977621b33245e359cce7a7e2cc978';
$now = date('Y-m-d H:i:s');
echo "=============={$now}==================\n";
if(!empty($ip)) {
    if(file_exists($ipFile)) {
        $oldIP = file_get_contents($ipFile);
        if($oldIP == $ip) {
            echo "ip not change\n";
            exit(0);
        }
    }
    echo "New IP $ip\n";

    $data = array(
        'login_token' => $loginToken,
        'format' => 'json',
        'domain' => 'orzorc.space'
    );
    $response = \Httpful\Request::post('https://dnsapi.cn/Record.List')
        ->body(http_build_query($data))
        ->contentType(\Httpful\Mime::FORM)
        ->send();
    $change = array();
    if($response->hasBody()) {
        $data = json_decode($response->body, true);
        if($data['status']['code'] == 1) {
            foreach($data['records'] as $record) {
                if($record['type'] == 'A' && $record['value'] != $ip) {
                    $change[] = $record['id'];
                }
            }
            if(!empty($change)) {
                $data = array(
                    'login_token' => $loginToken,
                    'format' => 'json',
                    'record_id' => implode(',', $change),
                    'change' => 'value',
                    'change_to' => $ip
                );
                $response = \Httpful\Request::post('https://dnsapi.cn/Batch.Record.Modify')
                    ->body(http_build_query($data))
                    ->contentType(\Httpful\Mime::FORM)
                    ->send();

                if($response->hasBody()) {
                    $data = json_decode($response->body, true);
                    if($data['status']['code'] == 1) {
                        $fp = fopen($ipFile, "w");
                        fwrite($fp, $ip);
                        fclose($fp);
                        exit(0);
                    } else {
                        echo "update fail\n";
                        exit(5);
                    }
                } else {
                    echo "update return fail\n";
                    exit(4);
                }
            } else {
                echo "no need to update";
                exit(6);
            }
        } else {
            echo "get list fail\n";
            exit(3);
        }
    } else {
        echo "get list return fail\n";
        exit(2);
    }
}

echo "get ip fail\n";
exit(1);
