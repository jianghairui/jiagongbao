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
        $app_key = 'sdf';
        $master_secret = 'fuckshit';
        $client = new JPush($app_key, $master_secret);
        halt($client);


//        $pusher = $client->push();
//        $pusher->setPlatform('all');
//        $pusher->addAllAudience();
//        $pusher->setNotificationAlert('Hello, JPush');
//        try {
//            $pusher->send();
//        } catch (\JPush\Exceptions\JPushException $e) {
//            // try something else here
//            print $e;
//        }
    }


}