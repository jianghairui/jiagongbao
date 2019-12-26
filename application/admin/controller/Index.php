<?php
namespace app\admin\controller;
use my\Auth;
use think\Db;
class Index extends Base
{
    //首页
    public function index() {
        $auth = new Auth();
        $authlist = $auth->getAuthList(session('admin_id'));

        try {
            $whereOrder = [
                ['del','=',0]
            ];
            $count['order_count'] = Db::table('mp_order')->where($whereOrder)->count();
            $whereUser = [
                ['del','=',0]
            ];
            $count['user_count'] = Db::table('mp_user')->where($whereUser)->count();
            $whereVip = [
                ['vip','=',1],
                ['del','=',0]
            ];
            $count['vip_count'] = Db::table('mp_user')->where($whereVip)->count();
            $whereMoney = [
                ['status','=',1]
            ];
            $count['money_count'] = Db::table('mp_vip_order')->where($whereMoney)->sum('pay_price');
            $count['vip_pv'] = Db::table('mp_user')->sum('vip_pv');

            $whereTobeContact = [
                ['status','=',0],
                ['contact','=',0]
            ];
            $count['tobe_contact_num'] = Db::table('mp_vip_order')->where($whereTobeContact)->count();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->assign('authlist',$authlist);
        $this->assign('count',$count);
        return $this->fetch();
    }

    //上传图片限制512KB
    public function uploadImage()
    {
        if (!empty($_FILES)) {
            if (count($_FILES) > 1) {
                return ajax('最多上传一张图片', 9);
            }
            $path = ajaxUpload(array_keys($_FILES)[0]);
            return ajax(['path' => $path]);
        } else {
            return ajax('请上传图片', 3);
        }
    }

//上传图片限制2048KB
    public function uploadImage2m()
    {
        if (!empty($_FILES)) {
            if (count($_FILES) > 1) {
                return ajax('最多上传一张图片', 9);
            }
            $path = ajaxUpload(array_keys($_FILES)[0], 2048);
            return ajax(['path' => $path]);
        } else {
            return ajax('请上传图片', 3);
        }
    }






}
