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
        halt($client);


        $pusher = $client->push();
        $pusher->setPlatform('all');
        $pusher->addAllAudience();
        $pusher->setNotificationAlert('Hello, JPush');
        try {
            $result = $pusher->send();
        } catch (\JPush\Exceptions\JPushException $e) {
            // try something else here
            print $e;
        }
        halt($result);
    }

    public function index() {
        try {
            $arr = [];
            $a = $arr['name'];
        } catch(\Exception $e) {
            echo 'LALLALA<br>';
        }
        echo 'I AM OK';
    }


}