<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/4/22
 * Time: 13:53
 */
namespace app\api\controller;
use EasyWeChat\Factory;
use think\Db;

class Index extends Common {

//轮播图列表
    public function slideList() {
        try {
            $where = [
                ['status','=',1]
            ];
            $list = Db::table('mp_slideshow')->where($where)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
//需求留言
    public function messageAdd() {
        $val['content'] = input('post.content');
        checkPost($val);
        $val['uid'] = $this->myinfo['uid'];
        $val['create_time'] = time();
        try {
            Db::table('mp_message')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
//需求留言列表
    public function messageList() {
        try {
            $list = Db::table('mp_message')->alias('m')
                ->join("mp_user u","m.uid=u.id","left")
                ->field("m.id,m.content,m.create_time,u.nickname,u.avatar")
                ->order(['m.id'=>'DESC'])
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
//影片列表
    public function filmList() {
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);
        $recommend = input('post.recommend','');
        $where = [];
        $count = Db::table('mp_film')->alias('f')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        try {
            if($recommend) {
                $where = [
                    ['f.recommend','=',1]
                ];
            }
            $list = Db::table('mp_film')->alias('f')
                ->join("mp_admin a","f.admin_id=a.id","left")
                ->field("f.id,f.pic,f.title,f.up_time,f.desc,f.content")
                ->order(['f.up_time'=>'DESC'])
                ->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        return ajax($list);
    }
//公司简介
    public function company() {
        try {
            $exist = Db::table('mp_company')->where('id','=',1)->find();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return ajax($exist);
    }
//活动列表
    public function activeList() {
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);
        $where = [
            ['start_time','<',time()]
        ];
        try {
            $count = Db::table('mp_activity')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_activity')
                ->field("id,title,origin_price,price,pic,start_time,end_time")
                ->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        foreach ($list as &$v) {
            if($v['end_time'] < time()) {
                $v['ifend'] = 1;
            }else {
                $v['ifend'] = 0;
            }
        }
        return ajax($list);
    }
//活动详情
    public function activityDetail() {
        $val['id'] = input('post.id');
        $this->checkPost($val);
        try {
            $where = [
                ['id','=',$val['id']]
            ];
            $info = Db::table('mp_activity')
                ->where($where)
                ->field("id,title,desc,origin_price,price,pic,explain")
                ->find();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }
//预约下单
    public function activityOrder() {
        $val['name'] = input('post.name');
        $val['tel'] = input('post.tel');
        $val['a_id'] = input('post.a_id');
        $val['num'] = input('post.num');
        checkPost($val);
        $val['uid'] = $this->myinfo['uid'];
        $val['create_time'] = time();
        $val['order_sn'] = create_unique_number('');
        if(!is_tel($val['tel'])) {
            return ajax('无效的手机号',6);
        }
        if(!if_int($val['num'])) {
            return ajax('invalid number',-4);
        }
        try {
            $where = [
                ['id','=',$val['a_id']]
            ];
            $activity_exist = Db::table('mp_activity')->where($where)->find();
            if(!$activity_exist) {
                return ajax('非法参数',-4);
            }
//            $whereOrder = [
//                ['uid','=',$val['uid']],
//                ['a_id','=',$val['a_id']]
//            ];
//            $order_exist = Db::table('mp_order')->where($whereOrder)->find();
//            if($order_exist) {
//                return ajax('已预订',45);
//            }
            if($activity_exist['start_time'] > time()) {
                return ajax('活动未开始',26);
            }
            if($activity_exist['end_time'] <= time()) {
                return ajax('活动已结束',25);
            }
            $val['unit_price'] = $activity_exist['price'];
            $val['total_price'] = $activity_exist['price'] * $val['num'];
            Db::table('mp_order')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
//加盟
    public function join() {
        $val['name'] = input('post.name');
        $val['tel'] = input('post.tel');
        $val['email'] = input('post.email');
        $val['address'] = input('post.address');
        checkPost($val);
        $val['uid'] = $this->myinfo['uid'];
        $val['create_time'] = time();
        if(!is_tel($val['tel'])) {
            return ajax('无效的手机号',6);
        }
        if(!is_email($val['email'])) {
            return ajax('无效的邮箱',7);
        }
        try {
            $where = [
                ['uid','=',$val['uid']]
            ];
            $exist = Db::table('mp_join')->where($where)->find();
            if($exist) {
                return ajax('已提交过申请',46);
            }
            Db::table('mp_join')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
//我的订单列表
    public function myOrderList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        try {
            $where = [
                ['o.uid','=',$this->myinfo['uid']]
            ];
            $list = Db::table('mp_order')->alias('o')
                ->join("mp_activity a","o.a_id=a.id","left")
                ->order(["o.id"=>"DESC"])
                ->where($where)
                ->field("o.*,a.title,a.pic")
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }

    public function groupQrcode() {
        try {
            $exist = Db::table('mp_company')->where('id','=',1)->find();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        $url = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . '/' . $exist['qrcode'];
        return ajax($url);
    }



}
