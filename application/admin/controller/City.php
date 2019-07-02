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
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2018/'. substr($code, 0,2) . '/' . $code . '.html');curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);curl_close($curl);$data = mb_convert_encoding($data, 'UTF-8', 'GBK');
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
        foreach ($out as $k=>$v) {
            if($k%2 == 0) {
                $list[] = [
                    'code' => $out[$k],
                    'name' => $out[$k+1],
                    'pcode' => $code,
                    'level' => 3
                ];
            }
        }
        if(!in_array($code,[1101,1201,3101,5001])) {
            unset($list[0]);
        }
        return $list;
    }

    public function getCity($code)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2018/' . $code . '.html');curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
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

        $list = [];
        foreach ($city as $k=>$v) {
            if($k%2 == 0) {
                $list[] = [
                    'code' => substr($city[$k],0,4),
                    'name' => $city[$k+1],
                    'pcode' => $code,
                    'level' => 2
                ];
                $list = array_merge($list,$this->getDistrict($city[$k]));
            }
        }
//        halt($list);
//        $res = Db::table('mp_city_bak')->insertAll($list);
//        halt($res);
    }


    public function getProvince()
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2018/index.html');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
// 裁头
        $offset = mb_strpos($data, 'provincetr',2000,'GBK');
        $data = mb_substr($data, $offset,NULL,'GBK');
// 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
        $data = mb_substr($data, 0, $offset,'GBK');
        preg_match_all('/\d{2}|[\x7f-\xff]+/', $data, $out);
        $province = $out[0];

        $list = [];

        foreach ($province as $k=>$v) {
            if($k%2 == 0) {
                $list[] = [
                    'code' => $province[$k],
                    'name' => $province[$k+1],
                    'pcode' => 0,
                    //'child' => $this->getCity($province[$j-1])
                ];
            }
        }

//        halt($list);
//        Db::table('mp_city_bak')->insertAll($list);
    }


//    public function test() {
//        $this->getCity('65');
//    }


}
