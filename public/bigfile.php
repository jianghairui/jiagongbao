<?php
date_default_timezone_set('PRC');
define('ROOT_PATH',__DIR__);
ini_set('memory_limit','512M');
class UploadService
{
//    private $filepath;
    private $blobNum; //第几个文件块
    private $totalBlobNum; //文件块总数
    private $tmpFile;  //PHP文件临时文件
    private $fileName; //文件名
    private $tmpPath;

    public function __construct($tmpFile,$blobNum,$totalBlobNum,$fileName){
        $this->tmpFile =  $tmpFile;
        $this->blobNum =  $blobNum;
        $this->totalBlobNum =  $totalBlobNum;
        $this->fileName =  $fileName;
        $this->tmpPath = ROOT_PATH.'/tmp/';
        $this->filepath = ROOT_PATH.'/'.date('Ymd',time()).'/';
        if(!file_exists($this->tmpPath)){
            mkdir($this->tmpPath,0755);
        }
        if(!file_exists($this->filepath)){
            mkdir($this->filepath,0755);
        }
    }
    //API返回数据
    public function apiReturn(){
        $this->moveFile();
        $this->fileMerge();
        if($this->blobNum == $this->totalBlobNum){
            if(file_exists($this->tmpPath.'/'. $this->fileName)){
                $data['code'] = 2;
                $data['msg'] = 'success';
                $data['file_path'] = $this->filepath .$this->fileName;
                copy($this->tmpPath.'/'. $this->fileName,$this->filepath.'/'. $this->fileName);
                @unlink($this->tmpPath.'/'. $this->fileName);
            }
        }else{
            if(file_exists($this->tmpPath.'/'. $this->fileName.'__'.$this->blobNum)){
                $data['code'] = 1;
                $data['msg'] = 'waiting for all';
                $data['file_path'] = '';
            }
        }
        header('Content-type: application/json');
        header('Access-Control-Allow-Origin: *');
        exit(json_encode($data));
    }

    //移动文件
    private function moveFile(){
        $filename = $this->tmpPath.'/'. $this->fileName.'__'.$this->blobNum;
        move_uploaded_file($this->tmpFile,$filename);
    }
    //判断是否是最后一块，如果是则进行文件合成并且删除文件块
    private function fileMerge() {
        if($this->blobNum == $this->totalBlobNum){
            $blob = '';
            for($i=1; $i<= $this->totalBlobNum; $i++){
                $blob .= file_get_contents($this->tmpPath.'/'. $this->fileName.'__'.$i);
            }
            file_put_contents($this->tmpPath.'/'. $this->fileName,$blob);
            $this->deleteFileBlob();
        }
    }
    //删除文件块
    private function deleteFileBlob(){
        for($i=1; $i<= $this->totalBlobNum; $i++){
            @unlink($this->tmpPath.'/'. $this->fileName.'__'.$i);
        }
    }

}
//实例化并获取系统变量传参
$upload = new UploadService($_FILES['file']['tmp_name'],$_POST['blob_num'],$_POST['total_blob_num'],$_POST['file_name']);
//调用方法，返回结果
$upload->apiReturn();