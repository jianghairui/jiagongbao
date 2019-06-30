<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/18
 * Time: 21:36
 */
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
class Common extends Controller {

    protected $cmd = '';
    protected $domain = '';
    protected $weburl = '';
    protected $mp_config = [];
    protected $myinfo = [];

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->cmd = request()->controller() . '/' . request()->action();
        $this->domain = 'j.jianghairui.com';
        $this->weburl = 'http://j.jianghairui.com/';
        $this->checkSession();
    }

    private function checkSession() {
        $noneed = [
            'Login/usernamelogin',
            'Login/loginsms',
            'Login/phonelogin',
            'Login/getpasswdsms',
            'Login/resetpasswd',
            'Api/getslidelist',
            'Api/getcatelist',
            'Api/getorderlist',
            'Api/getprovincelist',
            'Api/getcitylist',
            'Api/getregionlist',
            'Pay/wxpaynotify',
            'Pay/alipaynotify',
            'Qiniu/callback',
            'Qiniu/getuptoken',

        ];
        if (in_array($this->cmd, $noneed)) {
            return true;
        }else {
            $token = input('post.token');
            if(!$token) {
                throw new HttpResponseException(ajax('token is empty',-5));
            }
            try {
                $exist = Db::table('mp_user')->where([
                    ['token','=',$token],
                    ['last_login_time','>',time() - 3600*24*30]
                ])->find();

            }catch (\Exception $e) {
                throw new HttpResponseException(ajax($e->getMessage(),-1));
            }
            if($exist) {
                $this->myinfo = $exist;
                return true;
            }else {
                throw new HttpResponseException(ajax('invalid token',-3));
            }
        }

    }

    //获取设置参数
    protected function getSetting() {
        try {
            $info = Db::table('mp_setting')->where('id','=',1)->find();
        } catch(\Exception $e) {
            throw new HttpResponseException(ajax($e->getMessage(),-1));
        }
        return $info;
    }

    //Exception日志
    protected function excep($cmd,$str) {
        $file= ROOT_PATH . '/exception.txt';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }
    //支付回调日志
    protected function paylog($cmd,$str) {
        $file= ROOT_PATH . '/notify.txt';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }

    //七牛云回调日志
    protected function qiniulog($cmd,$str) {
        $file= ROOT_PATH . '/qiniu.log';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }

}