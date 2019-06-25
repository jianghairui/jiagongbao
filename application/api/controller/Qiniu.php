<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/25
 * Time: 21:29
 */
namespace app\api\controller;

use think\Db;
require_once ROOT_PATH . '/extend/qiniu/autoload.php';
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Qiniu extends Common {

    public function getUpToken() {
        $accessKey = config('qiniu_ak');
        $secretKey = config('qiniu_sk');
        $auth = new Auth($accessKey, $secretKey);
        $bucket = config('qiniu_bucket');
// 生成上传Token
        $callbackBody = [
            'fname' => 'test'.time() . '.mp4',
            'fkey' => time(),
            'desc' => '文件描述',
//            'uid' => $this->myinfo['id'],
        ];
//        return ajax($_SERVER);
        $policy = [
            'callbackUrl' => $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'].'/api/qiniu/callBack',
            'callbackBody' => json_encode($callbackBody)
        ];

        $token = $auth->uploadToken($bucket,null,3600);
        header('Access-Control-Allow-Origin: *');
//        exit($token);
        exit(json_encode([
            'uptoken' => $token,
            'domain' => 'up.qiniup.com'
        ]));
//        halt($token);
    }

    public function callBack() {

    }

}