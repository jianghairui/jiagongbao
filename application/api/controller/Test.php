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

class Test extends Controller {

    public function test() {
        $app_key = '8e123d2b7b9f85f29457b1fb';
        $master_secret = '84469df28646b679085ca3ca';
        $client = new JPush($app_key, $master_secret);

        $pusher = $client->push();
        $platform_all = 'all';
        $platform = ['ios', 'android'];
        $pusher->setPlatform($platform);
        $pusher->addAllAudience();
        $pusher->setNotificationAlert('欢迎使用加工宝APP');
        try {
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