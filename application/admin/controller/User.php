<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/5
 * Time: 21:45
 */
namespace app\admin\controller;

use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class User extends Base {

    public function userList() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['u.del','=',0]
        ];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['u.status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['u.create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['u.create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['u.tel','like',"%{$param['search']}%"];
        }

        try {
            $count = Db::table('mp_user')->alias('u')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_user')->alias('u')
                ->join('mp_userinfo i','u.id=i.uid','left')
                ->field('u.*,i.name,i.address,i.linkman,i.linktel,i.busine')
                ->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->order(['u.id'=>'DESC'])->select();
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }

    public function userAdd() {
        return $this->fetch();
    }

    public function userAddPost() {
        $user['tel'] = input('post.tel');
        $user['password'] = input('post.password');
        $user['status'] = input('post.status');
        $info['name'] = input('post.name');
        $info['linkman'] = input('post.linkman');
        $info['linktel'] = input('post.linktel');
        $info['address'] = input('post.address');
        $info['busine'] = input('post.busine');
        checkInput($user);
        checkInput($info);
        $user['vip_time'] = input('post.vip_time',0);

        if(!is_tel($user['tel']) || !is_tel($info['linktel'])) {
            return ajax('无效的手机号',-1);
        }
        $user['password'] = md5($user['password'] . config('login_key'));
        if($user['vip_time'] && strtotime($user['vip_time']) > time()) {
            $user['vip_time'] = strtotime($user['vip_time']);
            $user['vip'] = 1;
        }
        $user['create_time'] = time();

        try {
            $setting = $this->getSetting();
            $user['free_times'] = $setting['free_chance'];
            $whereUser = [
                ['tel','=',$user['tel']]
            ];
            $tel_exist = Db::table('mp_user')->where($whereUser)->find();
            if($tel_exist) {
                return ajax('账号已存在,请更换其他手机号',-1);
            }

            $uid = Db::table('mp_user')->insertGetId($user);
            $info['uid'] = $uid;
            $info['create_time'] = time();
            Db::table('mp_userinfo')->insert($info);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }


    public function userDetail() {
        $id = input('param.id',0);
        try {
            $where = [
                ['u.id','=',$id]
            ];
            $exist = Db::table('mp_user')->alias('u')->join('mp_userinfo i','u.id=i.uid','left')
                ->where($where)
                ->find();
            if(!$exist) {
                die('非法操作');
            }
        } catch(\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$exist);
        return $this->fetch();
    }

    public function userMod() {
        $user['tel'] = input('post.tel');
        $user['status'] = input('post.status');
        $user['id'] = input('post.id',0);
        $info['name'] = input('post.name');
        $info['linkman'] = input('post.linkman');
        $info['linktel'] = input('post.linktel');
        $info['address'] = input('post.address');
        $info['busine'] = input('post.busine');
        checkInput($user);
        checkInput($info);
        $user['vip_time'] = input('post.vip_time',0);
        $user['password'] = input('post.password');
        if(!is_tel($user['tel']) || !is_tel($info['linktel'])) {
            return ajax('无效的手机号',-1);
        }
        if($user['password']) {
            $user['password'] = md5($user['password'] . config('login_key'));
        }else {
            unset($user['password']);
        }
        if($user['vip_time'] && strtotime($user['vip_time']) > time()) {
            $user['vip_time'] = strtotime($user['vip_time']);
            $user['vip'] = 1;
        }else {
            $user['vip_time'] = strtotime($user['vip_time']);
            $user['vip'] = 0;
        }

        try {
            $user_exist = Db::table('mp_user')->where('id','=',$user['id'])->find();
            if(!$user_exist) {
                return ajax('非法操作',-1);
            }
            $whereUser = [
                ['tel','=',$user['tel']],
                ['id','<>',$user['id']]
            ];
            $tel_exist = Db::table('mp_user')->where($whereUser)->find();
            if($tel_exist) {
                return ajax('账号已存在,请更换其他手机号',-1);
            }

            Db::table('mp_user')->where('id','=',$user['id'])->update($user);
            Db::table('mp_userinfo')->where('uid','=',$user['id'])->update($info);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function userDel() {
        $id = input('post.id');
        try {
            $where = [
                ['id','=',$id]
            ];
            $exist = Db::table('mp_user')->where($where)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_user')->where($where)->update(['del'=>1]);
            $whereOrder = [
                ['uid','=',$id]
            ];
            Db::table('mp_order')->where($whereOrder)->update(['del'=>1,'token'=>'']);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function userStop() {

    }

    public function userStart() {

    }


    public function rechargeList() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];

        $where[] = ['o.status','=',1];

//        if(!is_null($param['status']) && $param['status'] !== '') {
//            $where[] = ['o.status','=',$param['status']];
//        }

        if($param['logmin']) {
            $where[] = ['o.pay_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['o.pay_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['o.pay_order_sn','like',"%{$param['search']}%"];
        }


        try {
            $total_income = Db::table('mp_vip_order')->alias('o')->where($where)->sum('o.pay_price');
            $count = Db::table('mp_vip_order')->alias('o')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_vip_order')->alias('o')
                ->join('mp_userinfo i','o.uid=i.uid','left')
                ->field('o.*,i.linktel,i.name')
                ->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
//        $this->assign('status',$param['status']);
        $this->assign('total_income',$total_income);
        return $this->fetch();
    }



}