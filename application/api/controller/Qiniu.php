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
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
class Qiniu extends Common {

    protected $accessKey;
    protected $secretKey;
    protected $bucket;
    protected $domain;

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->accessKey = config('qiniu_ak');
        $this->secretKey = config('qiniu_sk');
        $this->bucket = config('qiniu_bucket');
        $this->domain = config('qiniu_domain');
    }

    // 生成上传Token
    public function getUpToken() {
        $auth = new Auth($this->accessKey, $this->secretKey);
        $suffix = input('post.suffix');

        $token = $auth->uploadToken($this->bucket,null,3600);

        $data = [
            'token' => $token,
            'domain' => $this->domain,
            'imgUrl' => 'tmp/' . time() . $suffix
        ];
        return ajax($data);
    }

    //获取bucket列表
    public function getBucketList() {

        $auth = new Auth($this->accessKey, $this->secretKey);
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);
        $region = 'z0';
        try {
            list($info, $err) = $bucketManager->listbuckets($region);
            if ($err) {
                return ajax($err);
            } else {
                return ajax($info);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

    }


    public function getFileList() {

        $auth = new Auth($this->accessKey, $this->secretKey);
        $bucketManager = new BucketManager($auth);

// 要列取文件的公共前缀
        $prefix = 'tmp';
// 上次列举返回的位置标记，作为本次列举的起点信息。
        $marker = '';

// 本次列举的条目数
        $limit = 1000;

        $delimiter = '';

        list($ret, $err) = $bucketManager->listFilesv2($this->bucket, $prefix, $marker, $limit, $delimiter, true);

        if ($err) {
            return ajax($err,-111);
        } else {
            foreach ($ret as &$v) {
                $v = json_decode($v,true);
//                $v = json_decode($v,true)['item']['key'];
            }
            return ajax($ret);
        }
    }

//移动文件
    public function moveFile() {
        $auth = new Auth($this->accessKey, $this->secretKey);
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);

        $key = 'tmp/1561557242.mp4';
        $srcBucket = $this->bucket;
        $destBucket = $this->bucket;
        $srcKey = $key;
        $destKey = 'upload/1561557242.mp4';
        $err = $bucketManager->move($srcBucket, $srcKey, $destBucket, $destKey, true);

        if($err) {
            return ajax($err->message(),-1);
        }else {
            return ajax();
        }


    }

    public function deleteAfterDays() {
        $key = 'tmp/1561557108.mp4';
        $days = 1;

        $auth = new Auth($this->accessKey, $this->secretKey);
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);
        $err = $bucketManager->deleteAfterDays($this->bucket, $key, $days);
        if ($err) {
            halt($err);
        }else {
            echo 'SUCCESS';
        }
    }




















    public function callBack() {
        $_body = file_get_contents('php://input');
        $body = json_decode($_body, true);
        $this->qiniulog($this->cmd,var_export($body,true));

        header('Content-Type: application/json');

        $resp = array('ret' => 'success');

        echo json_encode($resp);
    }

}