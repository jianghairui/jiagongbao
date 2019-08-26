<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/8/26
 * Time: 18:48
 */
$_body = file_get_contents('php://input');
$body = json_decode($_body, true);
header('Content-Type: application/json');
$resp = array('ret' => 'success');
echo json_encode($resp);