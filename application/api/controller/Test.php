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

    public function index() {
        return $this->fetch();
    }

    public function jipush() {
        $app_key = '8e123d2b7b9f85f29457b1fb';
        $master_secret = '84469df28646b679085ca3ca';

        $alias = ['1','2'];
        $content = 'this is the content' . time();
        $params = [
            'type' => 1,
            'order_id' => '2'
        ];
        $ios_badge = 0;
        $title = 'This is title';
        try {
            $client = new JPush($app_key, $master_secret);
            $pusher = $client->push();

//        $platform_all = 'all';
            $platform = ['ios', 'android'];
//            $platform = ['android'];
            $pusher->setPlatform($platform);
//            $pusher->addAllAudience();
            $pusher->addAlias($alias);
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

    public function jgpush() {
        $app_key = '8e123d2b7b9f85f29457b1fb';
        $master_secret = '84469df28646b679085ca3ca';

        $order_id = input('post.order_id',1);
        $content = input('post.content','default content');
        $title = input('post.title','default title');
        $alias = input('post.alias',0);
        $plat = input('post.plat','all');

        $audience = [];
        $audience[] = $alias;
        $params = [
            'type' => 1,
            'order_id' => $order_id
        ];
        $ios_badge = 0;

        try {
            $client = new JPush($app_key, $master_secret);
            $pusher = $client->push();

            if($plat == 'all') {
                $platform = 'all';
            }else {
                $platform = [$plat];
            }
            $pusher->setPlatform($platform);
            $pusher->addAlias($audience);
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



}