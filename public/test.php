<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/25
 * Time: 21:10
 */
define('ROOT_PATH',dirname(__DIR__));
require ROOT_PATH . '/extend/qiniu/autoload.php';
use Qiniu\Auth;
$accessKey = 'frItACjCKcfgAM965WcfDv3J8H8sQJNq9fvUQooB';
$secretKey = 'OJg8WtoT-cyGdhFyJUShzaRGRdPcbhwP4g_rE7xC';
$auth = new Auth($accessKey, $secretKey);
$bucket = 'jiagongbao';
// 生成上传Token
$callbackBody = [
    'fname' => 'test'.time() . '.mp4',
    'fkey' => time(),
    'desc' => '文件描述',
//            'uid' => $this->myinfo['id'],
];
$policy = [
    'callbackUrl' => $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'].'/callback.php',
    'callbackBody' => json_encode($callbackBody)
];
$token = $auth->uploadToken($bucket,null,3600,$policy);

//$uploadMgr = new UploadManager();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>七牛云上传DEMO</title>
</head>
<body>
<form method="post" action="http://up.qiniup.com" enctype="multipart/form-data">
    <input name="token" type="hidden" value="<?php echo $token;?>">
    <input name="file" type="file" />
    <input type="submit" value="上传"/>
</form>
</body>
</html>

