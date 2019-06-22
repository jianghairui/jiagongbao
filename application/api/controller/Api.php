<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/2
 * Time: 15:14
 */
namespace app\api\controller;

use think\Db;
class Api extends Common {

    //判断是否完善信息
    public function checkComplete() {
        try {
            $where = [
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_userinfo')->where($where)->find();
            if($exist) {
                return ajax(true);
            }else {
                return ajax(false);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }

    //完善信息
    public function completeInfo() {

        $val['name'] = input('post.name');
        $val['address'] = input('post.address');
        $val['linkman'] = input('post.linkman');
        $val['linktel'] = input('post.linktel');
        $val['busine'] = input('post.busine');
        checkPost($val);
        $val['create_time'] = time();
        if(!is_tel($val['linktel'])) {
            return ajax('invalid tel',6);
        }
        try {
            $where = [
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_userinfo')->where($where)->find();
            if($exist) {
                Db::table('mp_userinfo')->where($where)->update($val);
            }else {
                $val['uid'] = $this->myinfo['id'];
                Db::table('mp_userinfo')->where($where)->insert($val);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();

    }

    //获取首页轮播图
    public function getSlideList() {
        try {
            $where = [
                ['status','=',1]
            ];
            $order = ['sort'=>'ASC'];
            $list = Db::table('mp_slideshow')
                ->where($where)->order($order)
                ->field('id,pic')->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //获取分类列表
    public function getCateList() {
        $where = [
            ['del','=',0]
        ];
        $order = ['sort'=>'ASC'];
        try {
            $data['count'] = Db::table('mp_order_cate')->where($where)->count();
            $data['list'] = Db::table('mp_order_cate')->where($where)->field('id,cate_name,pic')->order($order)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($data);
    }

    //获取订单列表
    public function getOrderList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',15);
        $cate_id = input('post.cate_id','');
        $search = input('post.search','');
        $order = ['id'=>'DESC'];
        try {
            $where = [
                ['status','=',1],
                ['del','=',0]
            ];
            if($search) {
                $where[] = ['title','like',"%{$search}%"];
            }
            $find_in_set = [];
            if($cate_id) {
                $find_in_set = "FIND_IN_SET('".$cate_id."',cate_ids)";
            }
            $list = Db::table('mp_order')
                ->where($where)
                ->where($find_in_set)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->order($order)
                ->field('id,pics,title,address,num,create_time')->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics'])[0];
            $v['create_time'] = date('Y-m-d',$v['create_time']);
        }
        return ajax($list);
    }

    //获取订单详情
    public function getOrderDetail() {
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
                ->field("id,title,address,cate_ids,material,num,end_time,desc,pics,file_path,compname,linkman,linktel,create_time")->find();
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

    //立即报价
    public function offerPrice() {
        $val['order_id'] = input('post.order_id');
        $val['price'] = input('post.price');
        checkPost($val);
        $val['uid'] = $this->myinfo['id'];
        $val['create_time'] = time();
        if(!is_currency($val['price'])) {
            return ajax('无效的金额',49);
        }
        try {
            $where = [
                ['id','=',$val['order_id']],
                ['status','=',1]
            ];
            $order_exist = Db::table('mp_order')->where($where)->find();
            if(!$order_exist) {
                return ajax('非法参数',-4);
            }
            $whereOffer = [
                ['uid','=',$this->myinfo['id']],
                ['order_id','=',$val['order_id']]
            ];
            $offer_exist = Db::table('mp_offer_price')->where($whereOffer)->find();

            if(!$this->myinfo['vip'] && $this->myinfo['free_times'] < 1) {
                return ajax('您已经没有报价次数了',50);
            }
            if($offer_exist) {
                $update_data = [
                    'price'=>$val['price']
                ];
                Db::table('mp_offer_price')->where($whereOffer)->update($update_data);
            }else {
                Db::table('mp_offer_price')->insert($val);
            }
            if(!$this->myinfo['vip']) {
                Db::table('mp_user')->where('id','=',$this->myinfo['id'])->setDec('free_times',1);
            }

        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();

    }

    //检查是否是会员
    public function checkVip() {
        if($this->myinfo['vip']) {
            return ajax(true);
        }else {
            return ajax(false);
        }
    }

    //获取省级列表
    public function getProvinceList() {
        try {
            $where = [
                ['pcode','=',0]
            ];
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //获取城市列表
    public function getCityList() {
        $val['provinceCode'] = input('post.province_code');
        try {
            if($val['provinceCode']) {
                $where = [
                    ['pcode','=',$val['provinceCode']],
                    ['level','=',2]
                ];
            }else {
                return ajax([]);
            }
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //获取区列表
    public function getRegionList() {
        $val['cityCode'] = input('post.city_code');
        try {
            if($val['cityCode']) {
                $where = [
                    ['pcode','=',$val['cityCode']],
                    ['level','=',3]
                ];
            }else {
                return ajax([]);
            }
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

//    public function getCitylist() {
//        try {
//            $citylist = Db::table('mp_city')->select();
//            $list = $this->sortMerge($citylist,0);
//        } catch(\Exception $e) {
//            return ajax($e->getMessage(),-1);
//        }
//        return ajax($list);
//    }
//
//
//    private function sortMerge($node,$pcode=0)
//    {
//        $arr = array();
//        foreach($node as $key=>$v)
//        {
//            if($v['pcode'] == $pcode)
//            {
//                $v['child'] = $this->sortMerge($node,$v['code']);
//                $arr[] = $v;
//            }
//        }
//        return $arr;
//    }



}