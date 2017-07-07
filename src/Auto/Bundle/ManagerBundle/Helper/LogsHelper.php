<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/10/29
 * Time: 下午5:01
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class LogsHelper extends AbstractHelper{

    public function addWeChatPayLog($message){

        $logs_path = $this->container->getParameter('logs_path')."/pay";

        $file = $logs_path."/wechat".date('Ymd').".log";

        $this->createFolder($logs_path);

        file_put_contents($file, $message."\n",FILE_APPEND);

    }


    public function createFolder($path)
    {
        if (!file_exists($path))
        {
            $this->createFolder(dirname($path));
            mkdir($path, 0777);
        }
    }

    /*
     * 记录支付回调log
     */
    public function addPayNotifyLog($type, $jsonContent)
    {
        $payNotifyLog = new \Auto\Bundle\ManagerBundle\Entity\PayNotifyLog();
        $payNotifyLog->setType($type)
            ->setJsonContent($jsonContent);
        $man = $this->em;
        $man->persist($payNotifyLog);
        $man->flush();
        return true;

    }

    /*
     * 记录大于短信失败log
     */
    public function addDayuErrorLogs($msg, $mobile,$appRoot,$type)
    {
        if(1 == $type){
            $action = 'regester';
        }elseif(2 == $type){
            $action = 'forget';
        }else{
            $action = 'login';
        }
        $dayu_root = $appRoot.'/logs/dayu_error.txt';
        $time = date('Y-m-d H:i:s');
        $txt = "[$time] $mobile $msg [$action]\n";
        file_put_contents($dayu_root, "$txt", FILE_APPEND);
    }
    
}