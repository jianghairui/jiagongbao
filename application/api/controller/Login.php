<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/20
 * Time: 16:36
 */
namespace app\api\controller;
use think\Db;
use my\Sendsms;
class Login extends Common {

    //注册账号发送短信
    public function registerSms() {
        $val['tel'] = input('post.tel');
        checkPost($val);
        $sms = new Sendsms();
        $tel = $val['tel'];

        if(!is_tel($tel)) {
            return ajax('invalid tel',6);
        }
        try {
            $whereUser = [
                ['tel','=',$val['tel']]
            ];
            $tel_exist = Db::table('mp_user')->where($whereUser)->find();
            if($tel_exist) {
                return ajax('此手机号已注册',52);
            }
            $param = [
                'tel' => $tel,
                'code' => mt_rand(100000,999999),
                'create_time' => time()
            ];
            $exist = Db::table('mp_verify')->where('tel',$tel)->find();
            if($exist) {
                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $tpl_code = 'SMS_168555514';
                $res = $sms->send($param,$tpl_code);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where('tel',$tel)->update($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($param);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->insert($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }
    //手机号注册
    public function register() {
        $val['tel'] = input('post.tel');
        $val['code'] = input('post.code');
        $val['password'] = input('post.password');
        checkPost($val);
        if(!is_tel($val['tel'])) {
            return ajax('invalid tel',6);
        }
        try {
            $password = md5($val['password'] . config('login_key'));
            $whereVerify = [
                ['tel','=',$val['tel']],
                ['code','=',$val['code']]
            ];
            $code_exist = Db::table('mp_verify')->where($whereVerify)->find();
            if($code_exist) {
                if((time() - $code_exist['create_time']) > 60*5) {
                    return ajax('验证码已过期',17);
                }
            }else {
                return ajax('验证码无效',16);
            }

            Db::table('mp_verify')->where($whereVerify)->delete();

            $whereUser = [
                ['tel','=',$val['tel']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if($user_exist) {
                return ajax('此手机号已注册',52);
            }else {
                $setting = $this->getSetting();
                $token = md5($val['tel'] . time());
                $insert_data = [
                    'tel' => $val['tel'],
                    'password' => $password,
                    'create_time' => time(),
                    'last_login_time' => time(),
                    'free_times' => $setting['free_chance'],
                    'token' => $token
                ];
                Db::table('mp_user')->insert($insert_data);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($token);
    }

    //账号密码登录
    public function usernameLogin() {
        $val['tel'] = input('post.tel');
        $val['password'] = input('post.password');
        checkPost($val);
        try {
            $whereUser = [
                ['tel','=',$val['tel']],
                ['password','=',md5($val['password'] . config('login_key'))],
                ['del','=',0]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if($user_exist) {
                $token = md5($val['tel'] . time());
                $update_data = [
                    'tel' => $val['tel'],
                    'last_login_time' => time(),
                    'token' => $token
                ];
                Db::table('mp_user')->where($whereUser)->update($update_data);
            }else {
                return ajax('账号密码不匹配',47);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($token);
    }

    //发送手机验证码,手机号登录模板
    public function loginSms() {
        $val['tel'] = input('post.tel');
        checkPost($val);
        $sms = new Sendsms();
        $tel = $val['tel'];

        if(!is_tel($tel)) {
            return ajax('invalid tel',6);
        }
        try {
            $param = [
                'tel' => $tel,
                'code' => mt_rand(100000,999999),
                'create_time' => time()
            ];
            $exist = Db::table('mp_verify')->where('tel',$tel)->find();
            if($exist) {
                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $res = $sms->send($param);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where('tel',$tel)->update($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($param);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->insert($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }

    //手机号登录
    public function phoneLogin() {
        $val['tel'] = input('post.tel');
        $val['code'] = input('post.code');
        checkPost($val);
        if(!is_tel($val['tel'])) {
            return ajax('invalid tel',6);
        }
        try {
            $whereVerify = [
                ['tel','=',$val['tel']],
                ['code','=',$val['code']]
            ];
            $code_exist = Db::table('mp_verify')->where($whereVerify)->find();
            if($code_exist) {
                if((time() - $code_exist['create_time']) > 60*5) {
                    return ajax('验证码已过期',17);
                }
            }else {
                return ajax('验证码无效',16);
            }
            $whereUser = [
                ['tel','=',$val['tel']],
                ['del','=',0]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if($user_exist) {
                $token = md5($val['tel'] . time());
                $update_data = [
                    'tel' => $val['tel'],
                    'last_login_time' => time(),
                    'token' => $token
                ];
                Db::table('mp_user')->where($whereUser)->update($update_data);
            }else {
                $setting = $this->getSetting();
                $token = md5($val['tel'] . time());
                $insert_data = [
                    'tel' => $val['tel'],
                    'create_time' => time(),
                    'last_login_time' => time(),
                    'free_times' => $setting['free_chance'],
                    'token' => $token
                ];
                Db::table('mp_user')->insert($insert_data);
            }
            Db::table('mp_verify')->where($whereVerify)->delete();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($token);

    }

    //发送手机验证码,找回密码模板
    public function getPasswdSms() {
        $val['tel'] = input('post.tel');
        checkPost($val);
        $sms = new Sendsms();
        $tel = $val['tel'];

        if(!is_tel($tel)) {
            return ajax('invalid tel',6);
        }
        try {
            $param = [
                'tel' => $tel,
                'code' => mt_rand(100000,999999),
                'create_time' => time()
            ];
            $exist = Db::table('mp_verify')->where('tel',$tel)->find();
            if($exist) {
                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $res = $sms->send($param,'SMS_168555513');
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where('tel',$tel)->update($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($param);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->insert($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }

    //重置密码
    public function resetPasswd() {
        $val['tel'] = input('post.tel');
        $val['code'] = input('post.code');
        checkPost($val);
        if(!is_tel($val['tel'])) {
            return ajax('invalid tel',6);
        }
        try {
            $whereVerify = [
                ['tel','=',$val['tel']],
                ['code','=',$val['code']]
            ];
            $code_exist = Db::table('mp_verify')->where($whereVerify)->find();
            if($code_exist) {
                if((time() - $code_exist['create_time']) > 60*5) {
                    return ajax('验证码已过期',17);
                }
            }else {
                return ajax('验证码无效',16);
            }
            Db::table('mp_verify')->where($whereVerify)->delete();

            $whereUser = [
                ['tel','=',$val['tel']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if($user_exist) {
                $token = md5($val['tel'] . time());
                $update_data = [
                    'tel' => $val['tel'],
                    'last_login_time' => time(),
                    'token' => $token
                ];
                Db::table('mp_user')->where($whereUser)->update($update_data);
            }else {
                return ajax('用户不存在',48);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($token);
    }

    //设置新密码
    public function setNewPasswd() {

        $val['password'] = input('post.password');
        checkPost($val);
        try {
            $update_data = [
                'password' => md5($val['password'] . config('login_key'))
            ];
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($update_data);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();

    }








}