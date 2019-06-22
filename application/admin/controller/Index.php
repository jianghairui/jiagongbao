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
        $this->assign('authlist',$authlist);
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
