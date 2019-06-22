<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;

class City extends Base
{

    private function getDistrict($code)
    {
        $code = substr($code, 0,4);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/'. substr($code, 0,2) . '/' . $code . '.html');curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);curl_close($curl);$data = mb_convert_encoding($data, 'UTF-8', 'GBK');
//        if(in_array($code,['4604'])) {
//            return [];
//        }
// 裁头
        $offset = @mb_strpos($data, 'countytr',2000,'GBK');

        if (!$offset) {
            $offset = @mb_strpos($data, 'towntr',2000,'GBK');
            if(!$offset) {
                dump($code);
                die('DIE');
            }
        }

        $data = mb_substr($data, $offset,NULL,'GBK');
// 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
        $data = mb_substr($data, 0, $offset,'GBK');
        preg_match_all('/\d{12}|[\x7f-\xff]+/', $data, $out);
        $out = $out[0];
// 某个城市
        $list = [];
        for ($j=0; $j < count($out) ; $j++) {
            $list[] = [
                'code'=> $out[$j],
                'name'=> $out[++$j],
                'pcode' => $code,
                'level' => 3
            ];
        }
//        halt($list);
        unset($list[0]);
//        $res = Db::table('mp_city')->insertAll($list);
//        halt($res);

        return $list;
    }

    public function getCity($code)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/' . $code . '.html');curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
        // 裁头
        $offset = mb_strpos($data, 'citytr',2000,'GBK');
        $data = mb_substr($data, $offset,NULL,'GBK');
        // 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
        $data = mb_substr($data, 0, $offset,'GBK');
        preg_match_all('/\d{12}|[\x7f-\xff]+/', $data, $city);
        $city = $city[0];
//        halt($city);

        $list = [];
        for ($j=0; $j < count($city) ; $j++) {
            $child = $this->getDistrict($city[$j]);
//            $list[] = [
//                'code'=> substr($city[$j], 0,4),
//                'name'=> $city[++$j],
//                'pcode' => $code,
//                'level' => 2
//            ];
            ++$j;
            $list = array_merge($list,$child);
//            if($j > 30) {
//                break;
//            }
        }
//        halt($list);
//        $res = Db::table('mp_city')->insertAll($list);
//        halt($res);
        return $list;
    }
/*
 * 4604 4419 4420
 * */
//    public function getProvince()
//    {
////        $arr = [53];// 53
////        $res = [];
////        foreach ($arr as $v) {
////            $res = array_merge($res,$this->getCity($v));
////        }
////        try {
////            Db::table('mp_city')->insertAll($res);
////        }catch (\Exception $e) {
////            echo $e->getMessage();
////        }
////        halt($res);
//
//
//
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/index.html');
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        $data = curl_exec($curl);
//        curl_close($curl);
//        $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
//// 裁头
//        $offset = mb_strpos($data, 'provincetr',2000,'GBK');
//        $data = mb_substr($data, $offset,NULL,'GBK');
//// 裁尾
//        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
//        $data = mb_substr($data, 0, $offset,'GBK');
//        preg_match_all('/\d{2}|[\x7f-\xff]+/', $data, $out);
//        $province = $out[0];
//        halt($province);
//
//        $list = [];
//        for ($j=0; $j < count($province) ; $j++) {
//            $list[] = [
//                'code' => $province[$j],
//                'name' => $province[++$j],
//                'pid' => 0,
//                //'child' => $this->getCity($province[$j-1])
//            ];
//        }
//        Db::table('mp_city')->insertAll($list);
//        halt($list);
//    }
//
//
//    public function test() {
//        $this->getCity('44');
//    }
//
//    public function tests() {
//        $this->getDistrict('4604');
//    }

}
