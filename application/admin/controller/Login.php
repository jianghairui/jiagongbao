<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/27
 * Time: 11:24
 */
namespace app\admin\controller;
use think\Db;
class Login extends Base {

    public function index() {
        if(session('username') && session('loginstatus') && session('loginstatus') == md5(session('username') . config('login_key'))) {
            $this->redirect('Index/index');
            exit();
        }

        $cookie = cookie('password');
        if(isset($cookie) && $cookie != '') {
            $data['username'] = cookie('username');
            $data['password'] = cookie('password');
            $data['remember_pwd'] = 1;
        }else {
            $data['username'] = '';
            $data['password'] = '';
            $data['remember_pwd'] = 0;
        }
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function login() {
        if(request()->isPost()) {
            $login_vcode = input('post.login_vcode');
            if(strtolower($login_vcode) !== strtolower(session('login_vcode'))) {
                $this->error('验证码错误',url('Login/index'));
            }
            $where['username'] = input('post.username');
            $where['password'] = md5(input('post.password') . config('login_key'));
            try {
                $result = Db::table('mp_admin')->where($where)->find();

            }catch (\Exception $e) {
                $this->error($e->getMessage(),url('Login/index'));
            }
            if($result) {
                session('login_vcode',null);

                if($result['status'] == 0 && $result['username'] !== config('superman')) {
                    exit($this->fetch('frozen'));
                }
                try {
                    Db::table('mp_admin')->where($where)->setInc('login_times');
                    Db::table('mp_admin')->where($where)->update(['last_login_time'=>time(),'last_login_ip'=>$this->getip()]);
                }catch (\Exception $e) {
                    $this->error($e->getMessage(),url('Login/index'));
                }
                session('loginstatus',md5(input('post.username') . config('login_key')));
                session('admin_id',$result['id']);
                session('username',$result['username']);
                session('realname',$result['realname']);
                session('login_times',$result['login_times'] + 1);
                session('last_login_time',$result['last_login_time']);
                session('last_login_ip',$result['last_login_ip']);

                if(input('post.remember_pwd') == 1) {
                    cookie('username',input('post.username'),3600*24*7);
                    cookie('password',input('post.password'),3600*24*7);
                }else {
                    cookie('username',null);
                    cookie('password',null);
                }
                $this->log('登录账号',0);
            }else {
                $this->error('用户名密码不匹配',url('Login/index'));exit();
            }
            $this->redirect(url('Index/index'));
//            $this->success('登陆成功',url('Index/index'));

        }
    }

    public function logout() {
        session(null);
        $this->redirect('Login/index');
    }

    public function vcode() {
        $vcode = generateVerify(200,50,2,4,24);
        session('login_vcode',$vcode);
    }

    public function personal() {
        $id = session('admin_id');
        $info = Db::table('mp_admin')->where('id','=',$id)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function modifyInfo() {
        $id = session('admin_id');
        $val['realname'] = input('post.realname');
        $val['gender'] = input('post.gender');
        $val['tel'] = input('post.tel');
        $val['email'] = input('post.email');
        checkInput($val);
        $val['password'] = input('post.password');
        $val['desc'] = input('post.desc');
        if($val['password']) {
            $val['password'] = md5($val['password'] . config('login_key'));
        }else {
            unset($val['password']);
        }
        try {
            Db::table('mp_admin')->where('id','=',$id)->update($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($val,1);
    }


    protected function log($detail = '', $type = 0) {
        $insert['detail'] = $detail;
        $insert['admin_id'] = session('admin_id');
        $insert['create_time'] = time();
        $insert['ip'] = $this->getip();
        $insert['type'] = $type;
        Db::table('mp_syslog')->insert($insert);
    }


    //获取访问来源IP
    private function getip() {
        $unknown = 'unknown';
        if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ( isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (false !== strpos($ip, ','))
            $ip = reset(explode(',', $ip));
        return $ip;
    }




}