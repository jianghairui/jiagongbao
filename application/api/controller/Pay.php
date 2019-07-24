<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/19
 * Time: 21:42
 */
namespace app\api\controller;

use think\Controller;
use think\Db;
require_once ROOT_PATH . '/extend/alipay/AopSdk.php';

class Pay extends Common {

//微信支付统一下单
    public function wxPay() {

        $val['pay_order_sn'] = input('post.pay_order_sn');
        checkPost($val);
        try {
            $where = [
                ['pay_order_sn','=',$val['pay_order_sn']],
                ['status','=',0],
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_vip_order')->where($where)->find();
            if(!$exist) {
                return ajax('订单不存在或状态已改变',44);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        $appid = config('appid');
        $mch_id = config('mch_id');
        $val['pay_order_sn'] = create_unique_number('');

        $total_price = $exist['pay_price'];
//        $total_price = 0.01;
        $arr = [
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => randomkeys(32),
            'sign_type' => 'MD5',
            'body' => '加工宝APP',
            'out_trade_no' => $exist['pay_order_sn'],
            'total_fee' => floatval($total_price)*100,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/api/pay/wxPayNotify",
            'trade_type' => 'APP'
        ];

        $arr['sign'] = getSign($arr);

        /*--------------微信统一下单--------------*/
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = curl_post_data($url, array2xml($arr));
        $result = xml2array($res);
        try {
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $prepay['appid'] = $result['appid'];
                $prepay['partnerid'] = $result['mch_id'];
                $prepay['prepayid'] = $result['prepay_id'];
                $prepay['package'] = 'Sign=WXPay';
                $prepay['noncestr'] = $result['nonce_str'];
                $prepay['timestamp'] = strval(time());
                $prepay['sign'] = getSign($prepay);
            } else {
                return ajax('微信下单失败',53);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        return ajax($prepay);

    }
//微信充值支付回调
    public function wxPayNotify() {
        //将返回的XML格式的参数转换成php数组格式
        $xml = file_get_contents('php://input');
        $data = xml2array($xml);
        if($data) {
            $this->paylog($this->cmd,var_export($data,true));
            if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                $pay_order_sn = $data['out_trade_no'];
                $map = [
                    ['pay_order_sn','=',$pay_order_sn],
                    ['status','=',0]
                ];
                try {
                    $exist = Db::table('mp_vip_order')->where($map)->find();
                    if($exist) {
                        $update_data = [
                            'status' => 1,
                            'method' => 1,
                            'pay_time' => time(),
                            'trans_id' => $data['transaction_id']
                        ];
                        Db::table('mp_vip_order')->where($map)->update($update_data);

                        $user = Db::table('mp_user')->where('id','=',$exist['uid'])->find();
                        if($user['vip_time'] > time()) {
                            $update_user = [
                                'vip' => 1,
                                'vip_time' => $user['vip_time'] + $exist['vip_days']*24*3600
                            ];
                        }else {
                            $update_user = [
                                'vip' => 1,
                                'vip_time' => time() + $exist['vip_days']*24*3600
                            ];
                        }
                        Db::table('mp_user')->where('id','=',$exist['uid'])->update($update_user);
                    }else {
                        $this->paylog($this->cmd,'未找到订单');
                    }
                }catch (\Exception $e) {
                    $this->excep($this->cmd,$e->getMessage());
                    exit(array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
                }
            }
        }
        exit(array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
    }


    public function aliPay() {
        $val['pay_order_sn'] = input('post.pay_order_sn');
        checkPost($val);
        try {
            $where = [
                ['pay_order_sn','=',$val['pay_order_sn']],
                ['status','=',0],
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_vip_order')->where($where)->find();
            if(!$exist) {
                return ajax('订单不存在或状态已改变',44);
            }
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        try {
            $aop = new \AopClient ();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = config('alipay_appid');
            $aop->rsaPrivateKey = config('alipay_rsaPrivateKey');
            $aop->alipayrsaPublicKey=config('alipay_alipayrsaPublicKey');
            $aop->apiVersion = '1.0';
            $aop->postCharset='utf-8';
            $aop->format='json';
            $aop->signType = 'RSA2';
//生成随机订单号
            $request = new \AlipayTradeAppPayRequest();

            $total_amount = floatval($exist['pay_price']);
//            $total_amount = 0.01;
            $out_trade_no = $val['pay_order_sn'];
//异步地址传值方式
            $request->setNotifyUrl("http://j.jianghairui.com/api/pay/aliPayNotify");
            $request->setBizContent("{\"out_trade_no\":\"".$out_trade_no."\",\"total_amount\":".$total_amount.",\"product_code\":\"QUICK_MSECURITY_PAY\",\"subject\":\"加工宝会员充值\"}");
            $result = $aop->sdkExecute($request);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

//        exit(htmlspecialchars($result));
        return ajax($result);

    }

    public function aliPayNotify() {
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey=config('alipay_alipayrsaPublicKey');

        $result = $aop->rsaCheckV1($_POST,NULL,$_POST['sign_type']);

        $this->paylog($this->cmd,var_export($_REQUEST,true));

        if($result) {
            /*业务逻辑代码*/
            if($_POST['trade_status'] == 'TRADE_SUCCESS' ){
                //业务处理
                $pay_order_sn = $_POST['out_trade_no'];
                $map = [
                    ['pay_order_sn','=',$pay_order_sn],
                    ['status','=',0]
                ];
                try {
                    $exist = Db::table('mp_vip_order')->where($map)->find();
                    if($exist) {
                        $update_data = [
                            'status' => 1,
                            'method' => 2,
                            'pay_time' => time(),
                            'trans_id' => $_POST['trade_no']
                        ];
                        Db::table('mp_vip_order')->where($map)->update($update_data);

                        $user = Db::table('mp_user')->where('id','=',$exist['uid'])->find();
                        if($user['vip_time'] > time()) {
                            $update_user = [
                                'vip' => 1,
                                'vip_time' => $user['vip_time'] + $exist['vip_days']*24*3600
                            ];
                        }else {
                            $update_user = [
                                'vip' => 1,
                                'vip_time' => time() + $exist['vip_days']*24*3600
                            ];
                        }
                        Db::table('mp_user')->where('id','=',$exist['uid'])->update($update_user);
                    }else {
                        $this->paylog($this->cmd,'订单不存在');
                    }
                }catch (\Exception $e) {
                    $this->excep($this->cmd,$e->getMessage());
                }
                exit('success');

            }else{
                exit('fail');
            }
        }else {
            $this->paylog($this->cmd,'rsaCheckV1 failed');
        }

    }









}