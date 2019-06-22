<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/15
 * Time: 22:38
 */
namespace app\api\controller;
use think\Db;
use my\Sendsms;
class My extends Common {


    //获取个人信息
    public function getMyinfo() {
        $id = $this->myinfo['id'];
        try {
            $where = [
                ['uid','=',$id]
            ];
            $data = Db::table('mp_userinfo')->where($where)->find();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $data['tel'] = $this->myinfo['tel'];
        $data['vip'] = $this->myinfo['vip'];
        $data['vip_time'] = $this->myinfo['vip_time'];
        $data['free_times'] = $this->myinfo['free_times'];
        return ajax($data);
    }

    //修改手机号发送短信
    public function changeTelSms() {
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

    //修改手机号
    public function changeTel() {
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
            $whereTel = [
                ['tel','=',$val['tel']],
                ['id','<>',$this->myinfo['id']]
            ];
            $tel_exist = Db::table('mp_user')->where($whereTel)->find();
            if($tel_exist) {
                return ajax('此手机号已注册',52);
            }

            Db::table('mp_verify')->where($whereVerify)->delete();
            $where = [
                ['id','=',$this->myinfo['id']]
            ];
            $update_data = [
                'tel' => $val['tel']
            ];
            Db::table('mp_user')->where($where)->update($update_data);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    //修改绑定密码
    public function changePassword() {
        $val['password'] = input('post.password');
        checkPost($val);
        try {
            $where = [
                ['id','=',$this->myinfo['id']]
            ];
            $update_data = [
                'password' => md5($val['password'] . config('login_key'))
            ];
            Db::table('mp_user')->where($where)->update($update_data);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    //外发订单
    public function myOrderList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',15);
        $order = ['id'=>'DESC'];
        $status = input('post.status');
        try {
            $where = [
                ['uid','=',$this->myinfo['id']]
            ];
            if(!is_null($status) && $status !== '') {
                $where[] = ['status','=',$status];
            }
            $list = Db::table('mp_order')
                ->where($where)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->order($order)
                ->field('id,pics,title,address,num,create_time,status')->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics'])[0];
            $v['create_time'] = date('Y-m-d',$v['create_time']);
        }
        return ajax($list);
    }


    //外发订单详情
    public function myOrderDetail() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $whereOrder = [
                ['id','=',$val['order_id']],
                ['status','=',1],
                ['uid','=',$this->myinfo['id']],
                ['del','=',0]
            ];
            $order_exist = Db::table('mp_order')
                ->where($whereOrder)
                ->field("id,title,address,cate_ids,material,num,end_time,desc,pics,file_path,compname,linkman,linktel")->find();
            if(!$order_exist) {
                return ajax('非法参数',-4);
            }

            $whereCollect = [
                ['uid','=',$this->myinfo['id']],
                ['order_id','=',$val['order_id']]
            ];
            $collect = Db::table('mp_collect')->where($whereCollect)->find();
            if($collect) {
                $order_exist['collect'] = true;
            }else {
                $order_exist['collect'] = false;
            }

            $whereOffer = [
                ['order_id','=',$val['order_id']]
            ];
            $order_exist['offer_count'] = Db::table('mp_offer_price')->where($whereOffer)->count();
            $catelist = Db::table('mp_order_cate')->field('id,cate_name')->select();
            $cate_ids = explode(',',$order_exist['cate_ids']);
            $cate_arr = [];
            foreach ($catelist as $v) {
                $cate_arr[$v['id']] = $v['cate_name'];
            }
            $cate_names = [];
            foreach ($cate_ids as $v) {
                if(isset($cate_arr[$v])) {
                    $cate_names[] = $cate_arr[$v];
                }
            }
            $order_exist['cate_names'] = $cate_names;
            $order_exist['free_times'] = $this->myinfo['free_times'];
            $order_exist['pics'] = unserialize($order_exist['pics']);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($order_exist);
    }

    //查看报价
    public function offerList() {
        $order = ['p.id'=>'DESC'];
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['p.order_id','=',$val['order_id']]
            ];
            $list = Db::table('mp_offer_price')->alias('p')
                ->join('mp_userinfo u','p.uid=u.uid','left')
                ->where($where)
                ->field('p.price,u.*')
                ->order($order)
                ->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //我的报价
    public function myOfferList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',15);
        $order = ['o.id'=>'DESC'];
        try {
            $whereOffer = [
                ['p.uid','=',$this->myinfo['id']],
                ['o.status','=',1],
                ['o.del','=',0]
            ];
            $list = Db::table('mp_offer_price')->alias('p')
                ->join('mp_order o','p.order_id=o.id','left')
                ->where($whereOffer)
                ->field('p.price,p.order_id,o.pics,o.title,o.address,o.num,o.create_time,o.status')
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->order($order)
                ->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics'])[0];
            $v['create_time'] = date('Y-m-d',$v['create_time']);
        }
        return ajax($list);
    }

    //报价详情
    public function offerDetail() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $whereOrder = [
                ['id','=',$val['order_id']],
                ['status','=',1],
                ['del','=',0]
            ];
            $order_exist = Db::table('mp_order')
                ->where($whereOrder)
                ->field("id,title,address,cate_ids,material,num,end_time,desc,pics,file_path,compname,linkman,linktel")->find();
            if(!$order_exist) {
                return ajax('非法参数',-4);
            }

            $whereCollect = [
                ['uid','=',$this->myinfo['id']],
                ['order_id','=',$val['order_id']]
            ];
            $collect = Db::table('mp_collect')->where($whereCollect)->find();
            if($collect) {
                $order_exist['collect'] = true;
            }else {
                $order_exist['collect'] = false;
            }

            $whereOffer = [
                ['uid','=',$this->myinfo['id']],
                ['order_id','=',$val['order_id']]
            ];
            $order_exist['price'] = Db::table('mp_offer_price')->where($whereOffer)->value('price');

            $catelist = Db::table('mp_order_cate')->field('id,cate_name')->select();
            $cate_ids = explode(',',$order_exist['cate_ids']);
            $cate_arr = [];
            foreach ($catelist as $v) {
                $cate_arr[$v['id']] = $v['cate_name'];
            }
            $cate_names = [];
            foreach ($cate_ids as $v) {
                if(isset($cate_arr[$v])) {
                    $cate_names[] = $cate_arr[$v];
                }
            }
            $order_exist['cate_names'] = $cate_names;
            $order_exist['free_times'] = $this->myinfo['free_times'];
            $order_exist['pics'] = unserialize($order_exist['pics']);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($order_exist);
    }

    //收藏订单
    public function myCollectList() {

    }

    //充值VIP下单
    public function recharge() {
        $val['vip_id'] = input('post.vip_id');
        checkPost($val);
        try {
            $where = [
                ['id','=',$val['vip_id']]
            ];
            $exist = Db::table('mp_vip')->where($where)->find();
            if(!$exist) {
                return ajax($val,-4);
            }
            $insert_data = [
                'pay_order_sn' => create_unique_number(''),
                'pay_price' => $exist['price'],
                'uid' => $this->myinfo['id'],
                'vip_id' => $val['vip_id'],
                'vip_days' => $exist['days'],
                'create_time' => time()
            ];
            Db::table('mp_vip_order')->insert($insert_data);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        return ajax($insert_data['pay_order_sn']);

    }


}