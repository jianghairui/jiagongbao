<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/8
 * Time: 14:03
 */
namespace app\admin\controller;

use think\Db;
require_once ROOT_PATH . '/extend/qiniu/autoload.php';
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;

class Order extends Base {

    public function orderList() {
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');
        $param['cate_id'] = input('param.cate_id');
        $param['status'] = input('param.status','');
        $param['provinceCode'] = input('param.provinceCode','');
        $param['cityCode'] = input('param.cityCode','');
        $param['regionCode'] = input('param.regionCode','');
        $page['query'] = http_build_query(input('param.'));
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);
        $where = [
            ['del','=',0]
        ];
        $order = ['id'=>'DESC'];
        if($param['search']) {
            $where[] = ['title|linktel|compname','like',"%{$param['search']}%"];
        }

        if($param['logmin']) {
            $where[] = ['create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['status'] !== '') {
            $where[] = ['status','=',$param['status']];
        }

        if($param['regionCode']) {
            $where[] = ['region_code','=',$param['regionCode']];
        }elseif ($param['cityCode']) {
            $where[] = ['city_code','=',$param['cityCode']];
        }elseif ($param['provinceCode']) {
            $where[] = ['province_code','=',$param['provinceCode']];
        }

        try {
            $find_in_set = [];
            if($param['cate_id']) {
                $find_in_set = "FIND_IN_SET('".$param['cate_id']."',cate_ids)";
            }

            $count = Db::table('mp_order')->where($where)->count();
            $list = Db::table('mp_order')
                ->order($order)
                ->where($find_in_set)
                ->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();

            $whereCate = [
                ['del','=',0]
            ];
            $cate_list = Db::table('mp_order_cate')->where($whereCate)->select();
            $whereCity = [
                ['pcode','=',0],
                ['level','=',1]
            ];
            $province_list = Db::table('mp_city')->where($whereCity)->select();
            $city_list = Db::table('mp_city')->where('pcode','=',$province_list[0]['code'])->select();
            $region_list = Db::table('mp_city')->where('pcode','=',$city_list[0]['code'])->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        $cateArr = [];
        foreach ($cate_list as $v) {
            $cateArr[$v['id']] = $v['cate_name'];
        }

        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        $this->assign('cateArr',$cateArr);
        $this->assign('cate_list',$cate_list);
        $this->assign('province_list',$province_list);
        $this->assign('city_list',$city_list);
        $this->assign('region_list',$region_list);
        return $this->fetch();
    }

    public function orderDetail() {
        $id = input('param.id',0);
        try {
            $whereOrder = [
                ['id','=',$id]
            ];
            $order_exist = Db::table('mp_order')->where($whereOrder)->find();
            if(!$order_exist) {
                die('非法参数');
            }

            $where = [
                ['del','=',0]
            ];
            $cate_list = Db::table('mp_order_cate')->where($where)->select();
            $whereCity = [
                ['pcode','=',0],
                ['level','=',1]
            ];
            $province_list = Db::table('mp_city')->where($whereCity)->select();
            $city_list = Db::table('mp_city')->where('pcode','=',$order_exist['province_code'])->select();
            $region_list = Db::table('mp_city')->where('pcode','=',$order_exist['city_code'])->select();
        } catch(\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('cate_list',$cate_list);
        $this->assign('province_list',$province_list);
        $this->assign('city_list',$city_list);
        $this->assign('region_list',$region_list);
        $this->assign('info',$order_exist);
        return $this->fetch();
    }

    //获取城市列表
    public function getCityList() {
        $val['provinceCode'] = input('post.provinceCode');
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
        $val['cityCode'] = input('post.cityCode');
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

    public function orderAdd() {
        try {
            $where = [
                ['del','=',0]
            ];
            $cate_list = Db::table('mp_order_cate')->where($where)->select();
            $whereCity = [
                ['pcode','=',0],
                ['level','=',1]
            ];
            $province_list = Db::table('mp_city')->where($whereCity)->select();
            $city_list = Db::table('mp_city')->where('pcode','=',$province_list[0]['code'])->select();
            $region_list = Db::table('mp_city')->where('pcode','=',$city_list[0]['code'])->select();
        } catch(\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('cate_list',$cate_list);
        $this->assign('province_list',$province_list);
        $this->assign('city_list',$city_list);
        $this->assign('region_list',$region_list);
        return $this->fetch();
    }

    public function orderAddPost() {
        $val['title'] = input('post.title');
        $val['province_code'] = input('post.provinceCode');
        $val['city_code'] = input('post.cityCode');
        $val['region_code'] = input('post.regionCode');
        $val['material'] = input('post.material');
        $val['num'] = input('post.num');
        $val['end_time'] = input('post.end_time');
        $val['desc'] = input('post.desc');
        $val['compname'] = input('post.compname');
        $val['linktel'] = input('post.linktel');
        $val['linkman'] = input('post.linkman');
        checkInput($val);
        $val['cate_ids'] = input('post.cate_ids',[]);
        $val['file_path'] = input('post.file_path','');
        $images = input('post.pic_url',[]);
        $val['create_time'] = time();
        $val['check_time'] = time();
        $val['status'] = 1;

        try {
            $province = Db::table('mp_city')->where('code','=',$val['province_code'])->find();
            $city = Db::table('mp_city')->where('code','=',$val['city_code'])->find();
            $region = Db::table('mp_city')->where('code','=',$val['region_code'])->find();
            if(!$province || !$city || !$region) {
                return ajax('无效的地区编码',-1);
            }
            $val['address'] = $province['name'] . '-' . $city['name'] . '-' . $region['name'];
            $val['order_sn'] = create_unique_number('');

            if(empty($val['cate_ids'])) {
                return ajax('至少选择一个分类',-1);
            }

            $val['cate_ids'] = implode(',',array_unique($val['cate_ids']));

            if(empty($images)) {
                return ajax('至少上传一张图片',-1);
            }
            if(count($images) > 9) {
                return ajax('最多上传9张图片',-1);
            }

            $image_array = [];
            foreach ($images as $v) {
                if(!file_exists($v)) {
                    return ajax('请重新上传图片',2);
                }
                $image_array[] = rename_file($v);
            }
            $val['pics'] = serialize($image_array);

            if($val['file_path']) {
                $val['file_path'] = $this->moveFile($val['file_path']);
            }

            Db::table('mp_order')->insert($val);
        } catch(\Exception $e) {
            foreach ($image_array as $v) {
                @unlink($v);
            }
            //todo 删除新附件
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function orderMod() {
        $val['title'] = input('post.title');
        $val['province_code'] = input('post.provinceCode');
        $val['city_code'] = input('post.cityCode');
        $val['region_code'] = input('post.regionCode');
        $val['material'] = input('post.material');
        $val['num'] = input('post.num');
        $val['end_time'] = input('post.end_time');
        $val['desc'] = input('post.desc');
        $val['compname'] = input('post.compname');
        $val['linktel'] = input('post.linktel');
        $val['linkman'] = input('post.linkman');
        $val['id'] = input('post.id');
        checkInput($val);
        $val['cate_ids'] = input('post.cate_ids',[]);
        $val['file_path'] = input('post.file_path','');
        $images = input('post.pic_url',[]);
        $val['create_time'] = time();
        $val['check_time'] = time();
        $val['status'] = 1;

        try {
            $whereOrder = [
                ['id','=',$val['id']]
            ];
            $order_exist = Db::table('mp_order')->where($whereOrder)->find();
            if(!$order_exist) {
                return ajax('非法参数',-1);
            }
            $old_pics = unserialize($order_exist['pics']);
            $province = Db::table('mp_city')->where('code','=',$val['province_code'])->find();
            $city = Db::table('mp_city')->where('code','=',$val['city_code'])->find();
            $region = Db::table('mp_city')->where('code','=',$val['region_code'])->find();
            if(!$province || !$city || !$region) {
                return ajax('无效的地区编码',-1);
            }
            $val['address'] = $province['name'] . '-' . $city['name'] . '-' . $region['name'];
            $val['order_sn'] = create_unique_number('');

            if(empty($val['cate_ids'])) {
                return ajax('至少选择一个分类',-1);
            }

            $val['cate_ids'] = implode(',',array_unique($val['cate_ids']));

            if(empty($images)) {
                return ajax('至少上传一张图片',-1);
            }
            if(count($images) > 9) {
                return ajax('最多上传9张图片',-1);
            }

            $image_array = [];
            foreach ($images as $v) {
                if(!file_exists($v)) {
                    return ajax('请重新上传图片',2);
                }
                $image_array[] = rename_file($v);
            }
            $val['pics'] = serialize($image_array);

            if($val['file_path']) {
                $val['file_path'] = $this->moveFile($val['file_path']);
            }

            Db::table('mp_order')->where($whereOrder)->update($val);
        } catch(\Exception $e) {
            foreach ($image_array as $v) {
                if(!in_array($v,$old_pics)) {
                    @unlink($v);
                }
            }
            if($val['file_path'] !== $order_exist['file_path']) {
                //todo 删除新附件
            }
            return ajax($e->getMessage(),-1);
        }
        foreach ($old_pics as $v) {
            if(!in_array($v,$image_array)) {
                @unlink($v);
            }
        }
        if($val['file_path'] !== $order_exist['file_path']) {
            //todo 删除老附件
        }
        return ajax();
    }

    public function orderDel() {
        $val['id'] = input('post.id');
        try {
            $where = [
                ['id','=',$val['id']]
            ];
            $exist = Db::table('mp_order')->where($where)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_order')->where($where)->update(['del'=>1]);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function orderPass() {
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $where = [
                ['id','=',$val['id']],
                ['status','=',0]
            ];
            $exist = Db::table('mp_order')->where($where)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_order')->where($where)->update(['status'=>1]);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function orderReject() {
        $val['id'] = input('post.id');
        $val['reason'] = input('post.reason');
        checkInput($val);
        try {
            $where = [
                ['id','=',$val['id']],
                ['status','=',0]
            ];
            $exist = Db::table('mp_order')->where($where)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_order')->where($where)->update(['status'=>2,'reason'=>$val['reason']]);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }




    //分类列表
    public function cateList() {
        $where = [
            ['del','=',0]
        ];
        try {
            $list = Db::table('mp_order_cate')->where($where)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        return $this->fetch();
    }
//添加分类
    public function cateAdd() {
        return $this->fetch();
    }
//添加分类POST
    public function cateAddPost() {
        $val['cate_name'] = input('post.cate_name');
        checkInput($val);
        $val['create_time'] = time();
        if(isset($_FILES['file'])) {
            $info = upload('file');
            if($info['error'] === 0) {
                $val['pic'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }
        try {
            Db::table('mp_order_cate')->insert($val);
        }catch (\Exception $e) {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        return ajax([]);
    }
//分类详情
    public function cateDetail() {
        $id = input('param.id');
        try {
            $info = Db::table('mp_order_cate')->where('id',$id)->find();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        return $this->fetch();
    }
//修改分类POST
    public function cateModPost() {
        $val['cate_name'] = input('post.cate_name');
        $val['id'] = input('post.id',0);
        checkInput($val);
        try {
            $exist = Db::table('mp_order_cate')->where('id',$val['id'])->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            if(isset($_FILES['file'])) {
                $info = upload('file');
                if($info['error'] === 0) {
                    $val['pic'] = $info['data'];
                }else {
                    return ajax($info['msg'],-1);
                }
            }
            Db::table('mp_order_cate')->where('id',$val['id'])->update($val);
        }catch (\Exception $e) {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        if(isset($val['pic'])) {
            @unlink($exist['pic']);
        }
        return ajax([]);
    }
//删除分类
    public function cateDel() {
        $id = input('post.id');
        try {
            $exist = Db::table('mp_order_cate')->where('id',$id)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_order_cate')->where('id',$id)->update(['del'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    //轮播图排序
    public function orderCateSort() {
        $val['id'] = input('post.id');
        $val['sort'] = input('post.sort');
        checkInput($val);
        try {
            Db::table('mp_order_cate')->update($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($val);
    }


    //七牛云移动文件
    private function moveFile($file_path) {

        $key = str_replace('http://' . $this->domain . '/','',$file_path);
        //todo 判断key是否存在

        $srcBucket = $this->bucket;
        $destBucket = $this->bucket;
        $srcKey = $key;
        $destKey = 'upload/' . explode('/',$key)[1];
        if($srcKey == $destKey) {
            return 'http://' . $this->domain . '/' . $destKey;
        }

        $auth = new Auth($this->accessKey, $this->secretKey);
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);
        $err = $bucketManager->move($srcBucket, $srcKey, $destBucket, $destKey, true);
        if($err) {
            throw new \Exception($err->message());
        }else {
            return 'http://' . $this->domain . '/' . $destKey;
        }
    }




}