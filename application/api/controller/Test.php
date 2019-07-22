<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/9
 * Time: 2:16
 */
namespace app\api\controller;

use JPush\Client as JPush;
use think\Controller;
use think\Db;

class Test extends Controller {

    public function test() {
        try {
            $find1 = "FIND_IN_SET('".'1'."',cate_ids)";
            $find2 = "FIND_IN_SET('".'10'."',cate_ids)";
            $list = Db::table('mp_order')->where($find1)->whereOr([
                ['id','<>',10]
            ])->fetchSql(true)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        halt($list);
    }

    public function testpush() {
        $app_key = '8e123d2b7b9f85f29457b1fb';
        $master_secret = '84469df28646b679085ca3ca';

        $alias = ['1','2'];
        $content = 'this is the content' . time();
        $params = [
            'order_id' => '2'
        ];
        $ios_badge = 0;
        $title = 'this is title';
        try {
            $client = new JPush($app_key, $master_secret);
            $pusher = $client->push();

//        $platform_all = 'all';
            $platform = ['ios', 'android'];
            $platform = ['android'];
            $pusher->setPlatform($platform);
            $pusher->addAllAudience();
//            $pusher->addAlias($alias);
            $pusher->iosNotification (
                $content, [
                'sound' => '1',
                'badge' => (int)$ios_badge,
                'content-available' => true,
                'category' => 'jiguang',
                'extras' => $params,
            ])->androidNotification ($content, [
                'title' => $title,
                //'build_id' => 2,
                'extras' => $params,
            ]);
            $result = $pusher->send();
        } catch (\JPush\Exceptions\JPushException $e) {
            // try something else here
            die($e->getMessage());
        }
        halt($result);
    }

    public function index() {
        $app_key = getenv('app_key');
        $master_secret = getenv('master_secret');
        $registration_id = getenv('registration_id');

        $client = new JPush($app_key, $master_secret);
    }


}