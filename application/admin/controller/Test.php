<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/28
 * Time: 0:57
 */
namespace app\admin\controller;


class Test extends Base {


    public function test() {
        $this->getErr();

        try {
//            $arr = [];
//            $b = $arr['name'];
        } catch(\Exception $e) {
            die('Err: ' . $e->getMessage()) ;
        }
        echo 'lalala';
    }

    public function getErr() {
        throw new \Exception('something error');
    }


}