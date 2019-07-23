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
            $tpl_code = 'SMS_168555512';
            $exist = Db::table('mp_verify')->where('tel',$tel)->find();
            if($exist) {
                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $res = $sms->send($param,$tpl_code);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where('tel',$tel)->update($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($param,$tpl_code);
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

    //外发订单列表
    public function myOrderList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',15);
        $order = ['id'=>'DESC'];
        $status = input('post.status');
        try {
            $where = [
                ['uid','=',$this->myinfo['id']],
                ['del','=',0]
            ];
            if(!is_null($status) && $status !== '') {
                $where[] = ['status','=',$status];
            }
            $list = Db::table('mp_order')
                ->where($where)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->order($order)
                ->field('id,pics,title,address,num,create_time,end_time,offer_num,status')->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $pics = unserialize($v['pics']);
            if(empty($pics)) {
                $v['pics'] = '';
            }else {
                $v['pics'] = $pics[0];
            }
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
//                ['status','=',1],
                ['uid','=',$this->myinfo['id']],
                ['del','=',0]
            ];
            $order_exist = Db::table('mp_order')
                ->where($whereOrder)
                ->field("id,title,address,cate_ids,material,num,end_time,offer_num,desc,pics,file_path,compname,linkman,linktel,status")->find();
            if(!$order_exist) {
                return ajax('订单不存在或状态已改变',4);
            }
            if(strtotime($order_exist['end_time']) < time()) {
                $order_exist['if_end'] = 1;
            }else {
                $order_exist['if_end'] = 0;
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

    //查看订单报价详情
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

    //修改订单
    public function orderMod() {
        $val['title'] = input('post.title');
        $val['province_code'] = input('post.province_code');
        $val['city_code'] = input('post.city_code');
        $val['region_code'] = input('post.region_code');
        $val['material'] = input('post.material');
        $val['num'] = input('post.num');
        $val['end_time'] = input('post.end_time');
        $val['desc'] = input('post.desc');
        $val['compname'] = input('post.compname');
        $val['linktel'] = input('post.linktel');
        $val['linkman'] = input('post.linkman');
        $val['id'] = input('post.id');
        checkPost($val);
        $val['cate_ids'] = input('post.cate_ids',[]);
        $images = input('post.pic_url',[]);
        $val['create_time'] = time();
        $val['check_time'] = time();
        $val['status'] = 0;

        try {
            $whereOrder = [
                ['id','=',$val['id']],
                ['status','=',2]
            ];
            $order_exist = Db::table('mp_order')->where($whereOrder)->find();
            if(!$order_exist) {
                return ajax('订单不存在或状态已改变',4);
            }
            $old_pics = unserialize($order_exist['pics']);
            $province = Db::table('mp_city')->where('code','=',$val['province_code'])->find();
            $city = Db::table('mp_city')->where('code','=',$val['city_code'])->find();
            $region = Db::table('mp_city')->where('code','=',$val['region_code'])->find();
            if(!$province || !$city || !$region) {
                return ajax('无效的地区编码',56);
            }
            $val['address'] = $province['name'] . '-' . $city['name'] . '-' . $region['name'];

            if(empty($val['cate_ids'])) {
                return ajax('至少选择一个分类',57);
            }

            $val['cate_ids'] = implode(',',array_unique($val['cate_ids']));

            if(empty($images)) {
                return ajax('至少上传一张图片',3);
            }
            if(count($images) > 9) {
                return ajax('最多上传9张图片',8);
            }

            $image_array = [];
            foreach ($images as $v) {
                if(!file_exists($v)) {
                    return ajax('请重新上传图片',51);
                }
                $image_array[] = rename_file($v);
            }
            $val['pics'] = serialize($image_array);

            Db::table('mp_order')->where($whereOrder)->update($val);
        } catch(\Exception $e) {
            foreach ($image_array as $v) {
                if(!in_array($v,$old_pics)) {
                    @unlink($v);
                }
            }
            return ajax($e->getMessage(),-1);
        }
        foreach ($old_pics as $v) {
            if(!in_array($v,$image_array)) {
                @unlink($v);
            }
        }
        return ajax();
    }


    //我的报价订单列表
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
                ->field('p.price,p.order_id,o.pics,o.title,o.address,o.num,o.create_time,o.end_time,o.status')
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->order($order)
                ->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $pics = unserialize($v['pics']);
            if(empty($pics)) {
                $v['pics'] = '';
            }else {
                $v['pics'] = $pics[0];
            }
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
                return ajax('订单不存在或状态已改变',4);
            }
            if(strtotime($order_exist['end_time']) < time()) {
                $order_exist['if_end'] = 1;
            }else {
                $order_exist['if_end'] = 0;
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
        if(!$this->myinfo['vip']) {
            $compname_len = mb_strlen($order_exist['compname'],"utf-8");
            if($compname_len > 4) {
                $order_exist['compname'] = mb_substr($order_exist['compname'],0,2,'utf-8') . str_pad('',($compname_len-4),"*") . mb_substr($order_exist['compname'],-2,2,'utf-8');
            }elseif ($compname_len > 2) {
                $order_exist['compname'] = mb_substr($order_exist['compname'],0,2,'utf-8') . str_pad('',($compname_len-2),"*");
            }
            $order_exist['linktel'] = substr_replace($order_exist['linktel'],'****',3,4);

            $linkman_len = mb_strlen($order_exist['linkman'],"utf-8");
            if($linkman_len > 1) {
                $order_exist['linkman'] = mb_substr($order_exist['linkman'],0,1,'utf-8') . str_pad('',($compname_len-1),"*");
            }

        }
        return ajax($order_exist);
    }

    //我的收藏订单列表
    public function myCollectList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',15);
        $order = ['id'=>'DESC'];
        try {
            $whereCollect = [
                ['uid','=',$this->myinfo['id']]
            ];
            $collect_orderids = Db::table('mp_collect')->where($whereCollect)->column('order_id');
            if(empty($collect_orderids)) {
                return ajax([]);
            }
            $where = [
                ['status','=',1],
                ['del','=',0],
                ['id','in',$collect_orderids]
            ];
            $list = Db::table('mp_order')
                ->where($where)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->order($order)
                ->field('id,pics,title,address,num,create_time,end_time')->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $pics = unserialize($v['pics']);
            if(empty($pics)) {
                $v['pics'] = '';
            }else {
                $v['pics'] = $pics[0];
            }
            $v['create_time'] = date('Y-m-d',$v['create_time']);
        }
        return ajax($list);
    }

    public function collectCancel() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['order_id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_collect')->where($where)->find();
            if(!$exist) {
                return ajax('非法参数',-4);
            }
            Db::table('mp_collect')->where($where)->delete();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }

    public function offerCancel() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['order_id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_offer_price')->where($where)->find();
            if(!$exist) {
                return ajax('非法参数',-4);
            }
            Db::table('mp_offer_price')->where($where)->delete();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
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

    //关于我们
    public function aboutUs() {
        try {
            $info = Db::table('mp_company')->where('id','=',1)->field('logo,name,intro')->find();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info);
    }
    //联系我们
    public function contactUs() {
        try {
            $info = Db::table('mp_company')->where('id','=',1)->field('name,email,qq,weixin,tel')->find();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info);
    }
    //获取平台各模块信息
    public function getAppInfo() {
        try {
            $info = Db::table('mp_setting')->where('id','=',1)->find();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info);
    }

    //统计进入充值页面次数
    public function vipPageViewTimes() {
        try {
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->setInc('vip_pv',1);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }


}