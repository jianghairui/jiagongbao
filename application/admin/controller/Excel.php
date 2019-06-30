<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/23
 * Time: 2:30
 */
namespace app\admin\controller;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Excel extends Base {

    public function orderList() {
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');
        $param['cate_id'] = input('param.cate_id');
        $param['status'] = input('param.status','');
        $param['provinceCode'] = input('param.provinceCode','');
        $param['cityCode'] = input('param.cityCode','');
        $param['regionCode'] = input('param.regionCode','');
        $page['query'] = http_build_query(input('param.'));
        $where = [
            ['del','=',0]
        ];
        $order = ['id'=>'DESC'];
        if($param['search']) {
            $where[] = ['title|linktel|compname','like',"%{$param['search']}%"];
        }

        if($param['logmin']) {
            $where[] = ['create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['status'] !== '') {
            $where[] = ['status','=',$param['status']];
        }

        if($param['regionCode']) {
            $where[] = ['region_code','=',$param['regionCode']];
        }elseif ($param['cityCode']) {
            $where[] = ['city_code','=',$param['cityCode']];
        }elseif ($param['provinceCode']) {
            $where[] = ['province_code','=',$param['provinceCode']];
        }

        try {
            $find_in_set = [];
            if($param['cate_id']) {
                $find_in_set = "FIND_IN_SET('".$param['cate_id']."',cate_ids)";
            }

            $count = Db::table('mp_order')->where($where)->count();
            $list = Db::table('mp_order')
                ->order($order)
                ->where($find_in_set)
                ->where($where)->select();

            $whereCate = [
                ['del','=',0]
            ];
            $cate_list = Db::table('mp_order_cate')->where($whereCate)->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        $cateArr = [];
        foreach ($cate_list as $v) {
            $cateArr[$v['id']] = $v['cate_name'];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('纸巾机统计');

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getRowDimension('1')->setRowHeight(30);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(30);
        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(30);
        $sheet->getColumnDimension('L')->setWidth(12);

        $sheet->getStyle('A:Z')->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('A1')->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('C')->getNumberFormat()->setFormatCode( \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);

        $sheet->mergeCells('A1:L1');

        $sheet->setCellValue('A1', '加工宝订单统计' . date('Y-m-d H:i:s') . ' 制表人:' . session('realname'));
        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', '订单标题');
        $sheet->setCellValue('C2', '地区');
        $sheet->setCellValue('D2', '数量');
        $sheet->setCellValue('E2', '材质');
        $sheet->setCellValue('F2', '分类');
        $sheet->setCellValue('G2', '公司名称');
        $sheet->setCellValue('H2', '联系人');
        $sheet->setCellValue('I2', '联系电话');
        $sheet->setCellValue('J2', '发布时间');
        $sheet->setCellValue('K2', '备注');
        $sheet->setCellValue('L2', '审核状态');
        $sheet->getStyle('A2:L2')->getFont()->setBold(true);

        $index = 3;
        foreach ($list as $v) {
            $sheet->setCellValue('A'.$index, $v['id']);
            $sheet->setCellValue('B'.$index, $v['title']);
            $sheet->setCellValue('C'.$index, $v['address']);
            $sheet->setCellValue('D'.$index, $v['num']);
            $sheet->setCellValue('E'.$index, $v['material']);

            $cate_arr=explode(',',$v['cate_ids']);
            $cate_name = [];
            foreach ($cate_arr as $fo) {
                if(isset($cateArr[$fo])) {
                    $cate_name[] = $cateArr[$fo];
                }
            }
            $sheet->setCellValue('F'.$index, implode(',',$cate_name));
            $sheet->setCellValue('G'.$index, $v['compname']);
            $sheet->setCellValue('H'.$index, $v['linkman']);
            $sheet->setCellValue('I'.$index, $v['linktel']);
            $sheet->setCellValue('J'.$index, date('Y-m-d H:i',$v['create_time']));
            $sheet->setCellValue('K'.$index, $v['desc']);
            switch ($v['status']) {
                case 0:$status='审核中';break;
                case 1:$status='已通过';break;
                case 2:$status='未通过';break;
                default:$status='异常';
            }
            $sheet->setCellValue('L'.$index, $status);
            $index++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器输出07Excel文件
//header(‘Content-Type:application/vnd.ms-excel‘);//告诉浏览器将要输出Excel03版本文件
        header('Content-Disposition: attachment;filename="订单统计'.date('Y-m-d').'.xlsx"');//告诉浏览器输出浏览器名称
        header('Cache-Control: max-age=0');//禁止缓存

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }


    public function userList() {

    }


}