<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/25
 * Time: 23:37
 */
define('APP_PATH',__DIR__);
define('ROOT_PATH',dirname(__DIR__));
$_body = file_get_contents('php://input');
$body = json_decode($_body, true);
paylog('callback.php',var_export($body,true));

header('Content-Type: application/json');

$resp = array('ret' => 'success');

echo json_encode($resp);
//回调日志
function paylog($cmd,$str) {
    $file= ROOT_PATH . '/notify.txt';
    $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
    if(false !== fopen($file,'a+')){
        file_put_contents($file,$text,FILE_APPEND);
    }else{
        echo '创建失败';
    }
}