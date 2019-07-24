<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/15
 * Time: 20:30
 */
namespace app\admin\controller;

use think\Db;
class System extends Base {

    public function setting() {
        try {
            $info = Db::table('mp_setting')->find();
        } catch(\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function settingMod() {
        $val['vip_price'] = input('post.vip_price');
        $val['free_chance'] = input('post.free_chance');
        $val['vip_desc'] = input('post.vip_desc');
        $val['pay_desc'] = input('post.pay_desc');
        $val['corporation'] = input('post.corporation');
        $val['bank_type'] = input('post.bank_type');
        $val['bank_account'] = input('post.bank_account');
        $val['treaty'] = input('post.treaty');
        checkInput($val);
        $val['allow_ip'] = input('post.allow_ip');

        if(!is_currency($val['vip_price'])) {
            return ajax('无效的金额',-1);
        }

        if($val['allow_ip']) {
            $ips = explode(',',$val['allow_ip']);
            foreach ($ips as $v) {
                if(!filter_var($v,FILTER_VALIDATE_IP)) {
                    return ajax('ip不合法',-1);
                }
            }
        }
        if(isset($_FILES['file'])) {
            $info = upload('file');
            if($info['error'] === 0) {
                $val['vip_pic'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }
        try {
            $exist = Db::table('mp_setting')->where('id','=',1)->find();
            Db::table('mp_setting')->where('id','=',1)->update($val);
            Db::table('mp_vip')->where('id','=',1)->update(['price'=>$val['vip_price']]);
        }catch (\Exception $e) {
            if(isset($val['vip_pic'])) {
                @unlink($val['vip_pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        if(isset($val['vip_pic'])) {
            @unlink($exist['vip_pic']);
        }
        return ajax($val);
    }

    public function syslog() {

        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',20);

        $where = [];
        if($param['logmin']) {
            $where[] = ['s.create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['s.create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['a.realname|s.detail','like',"%{$param['search']}%"];
        }
        try {
            $count = Db::table('mp_syslog')->alias('s')
                ->join('admin a','s.admin_id=a.id','left')
                ->where($where)->count();
            $list = Db::table('mp_syslog')->alias('s')
                ->join('admin a','s.admin_id=a.id','left')
                ->where($where)
                ->order(['create_time'=>'DESC'])
                ->field('s.*,a.realname,a.username')
                ->limit(($curr_page - 1)*$perpage,$perpage)
                ->select();
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }



}