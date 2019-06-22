<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/5
 * Time: 21:45
 */
namespace app\admin\controller;

use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class User extends Base {

    public function userList() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['u.status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['u.create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['u.create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['u.tel','like',"%{$param['search']}%"];
        }

        try {
            $count = Db::table('mp_user')->alias('u')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_user')->alias('u')
                ->join('mp_userinfo i','u.id=i.uid')
                ->field('u.*,i.name,i.address,i.linkman,i.linktel,i.busine')
                ->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }

    public function userDetail() {

    }

    public function userDel() {

    }

    public function userStop() {

    }

    public function userStart() {

    }

    public function toExcel() {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);

        $sheet->setTitle('SHIT-1');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A:Z')->applyFromArray($styleArray);
//$sheet->getRowDimension('7')->setRowHeight(100);
//$sheet->getStyle('A7:B7')->getFont()->setBold(true)->setName('Arial')->setSize(10);
//$sheet->getStyle('A7')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

        $sheet->getStyle('A')->getNumberFormat()->setFormatCode( \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);

        $sheet->setCellValue('A6', 120224199201080730);
        $sheet->setCellValue('A7', '120224199201080730');
        $sheet->setCellValue('B7', '13102163019');


        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器输出07Excel文件
//header(‘Content-Type:application/vnd.ms-excel‘);//告诉浏览器将要输出Excel03版本文件
        header('Content-Disposition: attachment;filename="01simple.xlsx"');//告诉浏览器输出浏览器名称
        header('Cache-Control: max-age=0');//禁止缓存

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }



}