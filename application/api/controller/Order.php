<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/15
 * Time: 19:03
 */
namespace app\api\controller;
use think\Db;

class Order extends Common {

    public function orderRelease() {
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
        checkPost($val);

        $val['cate_ids'] = input('post.cate_ids',[]);
        $images = input('post.pic_url',[]);
        $val['create_time'] = time();
        $val['check_time'] = time();
        $val['status'] = 0;
        $val['uid'] = $this->myinfo['id'];

        try {
            $province = Db::table('mp_city')->where('code','=',$val['province_code'])->find();
            $city = Db::table('mp_city')->where('code','=',$val['city_code'])->find();
            $region = Db::table('mp_city')->where('code','=',$val['region_code'])->find();
            if(!$province || !$city || !$region) {
                return ajax('无效的地区编码',56);
            }
            $val['address'] = $province['name'] . '-' . $city['name'] . '-' . $region['name'];
            $val['order_sn'] = create_unique_number('');

            if(!is_array($val['cate_ids']) || empty($val['cate_ids'])) {
                return ajax('至少选择一个分类',57);
            }

            $val['cate_ids'] = implode(',',array_unique($val['cate_ids']));

            if(!is_array($images) || empty($images)) {
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
            Db::table('mp_order')->insert($val);
        } catch(\Exception $e) {
            foreach ($image_array as $v) {
                @unlink($v);
            }
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }


}