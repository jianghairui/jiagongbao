<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/8
 * Time: 18:21
 */
namespace app\admin\controller;

use think\Exception;
use think\Db;
class Banner extends Base {
    //轮播图列表
    public function slideshow() {
        try {
            $list = Db::table('mp_slideshow')->order(['sort'=>'ASC'])->select();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        return $this->fetch();
    }
    //添加轮播图POST
    public function slideadd() {
        $val['title'] = input('post.title');
        checkInput($val);
        $val['url'] = input('post.url');

        if(isset($_FILES['file'])) {
            $info = upload('file');
            if($info['error'] === 0) {
                $val['pic'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }else {
            return ajax('请上传图片',-1);
        }

        try {
            $res = Db::table('mp_slideshow')->insert($val);
        }catch (\Exception $e) {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax('添加失败',-1);
        }
    }
    //修改轮播图
    public function slidemod() {
        $val['id'] = input('param.id');
        try {
            $exist = Db::table('mp_slideshow')->where('id','=',$val['id'])->find();
        } catch(\Exception $e) {
            die($e->getMessage());
        }
        if(!$exist) {
            $this->error('非法操作',url('Banner/slideshow'));
        }
        $this->assign('info',$exist);
        return $this->fetch();
    }
    //修改轮播图POST
    public function slidemod_post() {
        if(request()->isPost()) {
            $val['title'] = input('post.title');
            $val['id'] = input('post.slideid');
            checkInput($val);
            $val['url'] = input('post.url');
            try {
                $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
                if(!$exist) {
                    return ajax('非法操作',-1);
                }
                if(isset($_FILES['file'])) {
                    $info = upload('file');
                    if($info['error'] === 0) {
                        $val['pic'] = $info['data'];
                    }else {
                        return ajax($info['msg'],-1);
                    }
                }
                Db::table('mp_slideshow')->update($val);
            }catch (Exception $e) {
                if(isset($_FILES['file'])) {
                    @unlink($val['pic']);
                }
                return ajax($e->getMessage(),-1);
            }
            if(isset($_FILES['file'])) {
                @unlink($exist['pic']);
            }
            return ajax([],1);
        }
    }
    //删除轮播图
    public function slide_del() {
        $val['id'] = input('post.slideid');
        checkInput($val);

        try {
            $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }

            $model = model('Slideshow');
            $model::destroy($val['id']);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        return ajax([],1);
    }
    //轮播图排序
    public function sortSlide() {
        $val['id'] = input('post.id');
        $val['sort'] = input('post.sort');
        checkInput($val);
        try {
            Db::table('mp_slideshow')->update($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($val);
    }
    //禁用轮播图
    public function slide_stop() {
        $val['id'] = input('post.slideid');
        checkInput($val);
        try {
            $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_slideshow')->where('id',$val['id'])->update(['status'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //启用轮播图
    public function slide_start() {
        $val['id'] = input('post.slideid');
        checkInput($val);
        try {
            $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }

            Db::table('mp_slideshow')->where('id',$val['id'])->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function company() {
        if(request()->isPost()) {
            $val['name'] = input('post.name');
            $val['intro'] = input('post.intro');
            $val['email'] = input('post.email');
            $val['qq'] = input('post.qq');
            $val['weixin'] = input('post.weixin');
            $val['tel'] = input('post.tel');
            checkPost($val);
            if(isset($_FILES['file'])) {
                $info = upload('file');
                if($info['error'] === 0) {
                    $val['logo'] = $info['data'];
                }else {
                    return ajax($info['msg'],-1);
                }
            }
            try {
                $exist = Db::table('mp_company')->where('id',1)->find();
                if(!$exist) {
                    Db::table('mp_company')->insert($val);
                }else {
                    Db::table('mp_company')->where('id',1)->update($val);
                }
            }catch (\Exception $e) {
                if(isset($val['logo'])) {
                    @unlink($val['logo']);
                }
                return ajax($e->getMessage(),-1);
            }
            if($exist) {
                if(isset($val['logo'])) {
                    @unlink($exist['logo']);
                }

            }
            return ajax();
        }
        try {
            $exist = Db::table('mp_company')->where('id','=',1)->find();
        } catch(\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$exist);
        return $this->fetch();
    }


}