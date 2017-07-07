<?php
/**
 * Created by PhpStorm.
 * User: Tau
 * Date: 16/11/3
 * Time: 上午11:59
 */
include_once('TopSdk.php');


$c = new TopClient;
$c->appkey = "23487586";
$c->secretKey = '1dc2035c50760b6435348a586f72dfad';
$req = new AlibabaAliqinFcSmsNumSendRequest;
$req->setExtend("123456");
$req->setSmsType("normal");
$req->setSmsFreeSignName("登录验证");
$req->setSmsParam("{\"code\":\"24113\",\"product\":\"云杉驾呗\"}");
$req->setRecNum("15652669326");
$req->setSmsTemplateCode("SMS_7495828");
$resp = $c->execute($req);
var_dump($resp);exit;