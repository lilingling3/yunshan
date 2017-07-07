<?php
/**
 * Created by PhpStorm.
 * User: liyandong
 * Date: 16/8/5
 * Time: 17:39
 * 运管局数据传输
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class TsaCommand extends ContainerAwareCommand{

    private $tmplocalPath = '';//文件临时目录
    private $outlocalPath = '';//生成文件完成后存放的目录
    private $assInfo = array();//车辆归属信息
    private $wayInfo = array();//路单（结算单）信息

    const IN_CHARSET = 'utf-8';
    const OUT_CHARSET = 'gbk//ignore';

    public function configure()
    {
        $this
            ->setName('auto:tsa')
            ->setDescription('send data to tsa')
//            ->addOption('day', 'day', InputOption::VALUE_OPTIONAL, 'day')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$this->getContainer()->getParameter("logs_path");die;
        $this->tmplocalPath = '/letv/data/tsa_csv/tmp/'.date('Ymd');
        $this->outlocalPath = '/letv/data/tsa_csv/out/'.date('Ymd');
//        date_default_timezone_set("PRC");
//        $date = date('Y-m-d',strtotime("-1 day"));
        dump('-----------租赁企业基本信息-----------start--------------');
        $this->ComInfo();
        dump('-----------租赁企业基本信息------------end---------------');

        dump('-----------租赁门店基本信息-----------start--------------');
        $this->DepInfo();
        dump('-----------租赁门店基本信息------------end---------------');

        dump('-----------租赁车辆基本信息-----------start--------------');
        $this->CarInfo();
        dump('-----------租赁车辆基本信息------------end---------------');

        dump('-----------租赁车辆归属信息-----------start--------------');
        $this->AssInfo();
        dump('-----------租赁车辆归属信息------------end---------------');

        dump('-----------租赁合同信息-----------start--------------');
        $this->ConInfo();
        dump('-----------租赁合同信息------------end---------------');

        dump('-----------租赁路单（结算单）信息-----------start--------------');
        $this->WayInfo();
        dump('-----------租赁路单（结算单）信息------------end---------------');

        dump('-----------车辆年检记录-----------start--------------');
        $this->InspInfo();
        dump('-----------车辆年检记录------------end---------------');

        dump('-----------车辆保养记录-----------start--------------');
        $this->MntInfo();
        dump('-----------车辆保养记录------------end---------------');

        dump('-----------车辆维修记录-----------start--------------');
        $this->RepInfo();
        dump('-----------车辆维修记录------------end---------------');

        dump( 'send data to TSA over! ' );
    }

    /**
     * 车辆维修记录
     */
    protected function RepInfo(){
        $date = date('Y-m-d',strtotime("-1 day"));
        $start_time = $date.' 00:00:00';
        $end_time = $date.' 23:59:59';
        $man = $this->getContainer()->get('doctrine')->getManager();
        $qb = $man
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->createQueryBuilder('o');
        $results =
            $qb
                ->select('o')
                ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->orderBy('o.createTime', 'ASC')
                ->getQuery()
                ->getResult();

        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('REP');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($results as $value) {
            $data = array();
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getId());
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getRentalCar()->getLicense());
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$value->getMaintenanceTime()->getTimestamp()));
            $data[] = '';//车辆状态
            $data[] = '';//承租方结算
            $data[] = '';//出租方结算
            $data[] = '';//保险理赔
            $data[] = '';//总结算金额
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getMaintenanceReason());//详细信息

            fputcsv($fp, $data);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }

    /**
     * 车辆保养记录
     */
    protected function MntInfo(){
        $date = date('Y-m-d',strtotime("-1 day"));
        $start_time = $date.' 00:00:00';
        $end_time = $date.' 23:59:59';
        $man = $this->getContainer()->get('doctrine')->getManager();
        $qb = $man
            ->getRepository('AutoManagerBundle:Upkeep')
            ->createQueryBuilder('o');
        $results =
            $qb
                ->select('o')
                ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->orderBy('o.createTime', 'ASC')
                ->getQuery()
                ->getResult();

        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('MNT');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($results as $value) {
            $data = array();
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getId());
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getRentalCar()->getLicense());
            if($value->getUpkeepTime()){
                $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$value->getUpkeepTime()->getTimestamp()));
            }else{
                $data[] = '';
            }
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$value->getNextUpkeepTime()->getTimestamp()));
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,floor(( $value->getNextUpkeepTime()->getTimestamp() - time() )/(3600*24)));
            $mileageCurrent = $this->getContainer()->get('auto_manager.rental_car_helper')->get_rental_car_current_mileage($value->getRentalCar());
            $data[] = $mileageCurrent;
            $data[] = $value->getNextMileage();
            $data[] = $value->getNextMileage() - $mileageCurrent;
            $data[] = '';
            fputcsv($fp, $data);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }

    /**
     * 车辆年检记录
     */
    protected function InspInfo(){
        $man = $this->getContainer()->get('doctrine')->getManager();
        $results = $man
            ->getRepository('AutoManagerBundle:Inspection')
            ->findAll();
        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('INSP');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");
        $inspTime = array();
        foreach ($results as $value) {
            $data = array();
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getId());
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getRentalCar()->getLicense());
            if($value->getInspectionTime()){
                $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,'已年检');
                $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$value->getInspectionTime()->getTimestamp()));
            }else{
                $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,'未年检');
                $data[] = '';
            }
            $rentalCarId = $value->getRentalCar()->getId();
            if(isset($inspTime[$rentalCarId])){
                $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$inspTime[$rentalCarId]['inspLastTime']));
            }else{
                $data[] = '';
            }
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$value->getNextInspectionTime()->getTimestamp()));

            if(isset($inspTime[$rentalCarId])){
                $day = floor(($value->getInspectionTime()->getTimestamp()-$inspTime[$rentalCarId]['inspNextTime'])/(3600*24));
                if($day > 0){
                    $data[] = 1;
                }else{
                    $data[] = 0;
                }
                $data[] = $day;
            }else{
                $data[] = 0;
                $data[] = 0;

            }
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$value->getCreateTime()->getTimestamp()));
            $data[] = '';
            $data[] = '';
            $data[] = '';
            $inspTime[$rentalCarId]['inspLastTime'] = $value->getInspectionTime()->getTimestamp();
            $inspTime[$rentalCarId]['inspNextTime'] = $value->getNextInspectionTime()->getTimestamp();

            fputcsv($fp, $data);
        }
        fclose($fp);
        unset($inspTime);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }

    /**
     * 租赁企业基本信息
     */
    protected function ComInfo(){
        $man = $this->getContainer()->get('doctrine')->getManager();
        $results = $man
            ->getRepository('AutoManagerBundle:Company')
            ->findBy(['kind'=> 6]);

        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('COM');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($results as $value) {
            $data = array(
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getName()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getCaseNo()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getArea()->getParent()->getParent()->getName()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getArea()->getParent()->getName()),
            );
            fputcsv($fp, $data);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }
    /**
     * 租赁门店基本信息
     */
    protected function DepInfo(){
        $man = $this->getContainer()->get('doctrine')->getManager();
        $results = $man
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findAll();

        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('DEP');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($results as $value) {
            $data = array(
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getName()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getCompany()->getName()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getArea()->getParent()->getParent()->getName()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getArea()->getParent()->getName()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getArea()->getName()),
                iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getStreet()),
            );
            $data[] = '';
            $data[] = '';
            fputcsv($fp, $data);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }

    /**
     * 租赁车辆基本信息
     * 车牌号	如：京C12345	CAR_BOARD	必填
    发动机号		CAR_ENGINE_CODE
    车架号/VIN	WDDUG6FB4EA058760	CAR_VIN
    品牌	奔驰	CAR_BRAND	必填
    型号	奔驰S500	CAR_BRANDMODEL	必填
    车身颜色	黑色	CAR_COLOR	必填
    车辆状态	（出租、处置、待租、公务、维修、已销售、整备）	CAR_STATUS	必填
    车种	豪华型	CAR_TYPE
    排量(不含单位L)	3.0	CAR_ DISPLACEMENT
    座位数	5	CAR_ SEATING
    行驶里程数		CAR_KM	必填
    车辆注册登记日期	2014-12-24	CAR_REGISTER	必填
    车辆购置价格(不含单位)	以万为单位（不含单位），如：19.00	CAR_PRICE	必填
    其他参数描述	3.0/自动挡/前驱/三厢/5座(品牌/排量/挡别/驱动方式/厢数/座位数)	CAR_OTHER
     */
    protected function CarInfo(){
        $man = $this->getContainer()->get('doctrine')->getManager();
        $results = $man
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findAll();

        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('CAR');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($results as $key => $value) {
            if(empty($value->getRegisterDate())){
                continue;
            }
            $data = array();
            $this->assInfo[$key][] = $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getLicense());//车牌号
            $this->assInfo[$key][] = '';
            $this->assInfo[$key][] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getRentalStation()->getName());//使用门店
            $data[] = '';//iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getEngineNumber()),
            $data[] = '';//iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getChassisNumber()),
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getCar()->getBrand());//品牌
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getCar()->getName());//型号
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getColor()->getName());//车身颜色
            $status = '待租';
            $this->assInfo[$key][] = $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$status);//车辆状态  （出租、处置、待租、公务、维修、已销售、整备）
            $data[] = '';//iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getCar()->getBodyType()->getName()),
            $data[] = '';
            $data[] = '';//iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getCar()->getSeats()),
            $data[] = $this->getContainer()->get('auto_manager.rental_car_helper')->get_rental_car_current_mileage($value);;//行驶里程数
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d',$value->getRegisterDate()->getTimestamp()));//车辆注册登记日期
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getBuyPrice());//车辆购置价格(不含单位)
            $data[] = '';
            fputcsv($fp, $data);
            unset($data);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }

    /**
     * 租赁车辆归属信息
     */
    protected function AssInfo(){
        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('ASS');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($this->assInfo as $value) {
            fputcsv($fp, $value);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }

    /**
     * 租赁合同信息
     */
    protected function ConInfo(){
        $date = date('Y-m-d',strtotime("-1 day"));
        $start_time = $date.' 00:00:00';
        $end_time = $date.' 23:59:59';
        $man = $this->getContainer()->get('doctrine')->getManager();
        $qb = $man
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->createQueryBuilder('o');
        $results =
            $qb
                ->select('o')
                ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                ->andWhere($qb->expr()->isNotNull('o.payTime'))
                ->andWhere($qb->expr()->isNotNull('o.useTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->orderBy('o.createTime', 'ASC')
                ->getQuery()
                ->getResult();

        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('CON');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($results as $key => $value) {
            if($value->getEndMileage()-$value->getStartMileage() <= 0){
                continue;
            }
            $data = array();
            $this->wayInfo[$key][] = $data[] = $value->getId();
            $this->wayInfo[$key][] = $value->getId();
            $this->wayInfo[$key][] = $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getRentalCar()->getLicense());//车牌号
            $order_start_time = $value->getUseTime()->getTimestamp();
            $order_end_time = $value->getEndTime()->getTimestamp();
            $time_result = $this->timediff($order_start_time,$order_end_time);
            $data[] = $data[] = 0;
            $this->wayInfo[$key][] = $this->wayInfo[$key][] = 0;
            $this->wayInfo[$key][] = $data[] = $time_result['day'];
            $this->wayInfo[$key][] = $data[] = $time_result['hour'];
            $this->wayInfo[$key][] = $data[] = $time_result['min'];

            $this->wayInfo[$key][] = $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$order_start_time));
            $this->wayInfo[$key][] = $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$order_end_time));
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getPickUpStation()->getName());
            $this->wayInfo[$key][] = $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getStartMileage());
            $this->wayInfo[$key][] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getEndMileage());
            $this->wayInfo[$key][] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getEndMileage()-$value->getStartMileage());

            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,date('Y-m-d H:i:s',$value->getCreateTime()->getTimestamp()));
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getMember()->getName());
            $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getMember()->getIdNumber());
            $data[] = '';
            $data[] = '';
            $this->wayInfo[$key][] = $data[] = iconv(self::IN_CHARSET,self::OUT_CHARSET,$value->getDueAmount());

            fputcsv($fp, $data);
            unset($data);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }

    /**
     * 租赁路单（结算单）信息
     */
    protected function WayInfo(){
        list($tmpFilePath,$outFilePath,$file_name) = $this->createFilePath('WAY');
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $fp = fopen($tmpFile,"a");

        foreach ($this->wayInfo as $value) {
            fputcsv($fp, $value);
        }
        fclose($fp);
        unset($results);

        $zipFile = $this->createZip($tmpFilePath,$outFilePath,$file_name);
        $this->sftp_send($zipFile);
        return true;

    }




    public function createZip($tmpFilePath,$outFilePath,$file_name){
        $tmpFile = $tmpFilePath.'/'.$file_name.'.csv';
        $tem_file_name = $file_name.'_'.filesize($tmpFile)  ;
        $tempFile2 = $tmpFilePath . "/".$tem_file_name.'.csv';
        exec('cp '.$tmpFile.' '.$tempFile2);
        exec('cd '.$tmpFilePath.'
        zip '.$file_name.'.zip'.' '.$tem_file_name.'.csv');
        $tmpZip = $tmpFilePath.'/'.$file_name.'.zip';
        $outZip = $outFilePath . "/".$file_name.'_'.filesize($tmpZip).'.zip';
        exec('cp '.$tmpZip.' '.$outZip);
        dump($outZip.' create ok!');
        return $outZip;
    }

    public function sftp_send($file){
        $strServer = "zulin.bjysgl.cn";
        $strServerPort = "22122";
        $strServerUsername = "lplx";
        $strServerPassword = "lplx001540";
        $path    =    'COLLECTION/'.basename($file);//要操作的远程根目录

//        $strServer = "vmlecar.local";
//        $strServerPort = "22";
//        $strServerUsername = "root";
//        $strServerPassword = "liyandong";
//        $path = '/letv/data/COLLECTION/'.basename($file);//要操作的远程根目录

        $resConnection = ssh2_connect($strServer, $strServerPort);

        if(ssh2_auth_password($resConnection, $strServerUsername, $strServerPassword))
        {
            $resSFTP = ssh2_sftp($resConnection);

            if(!copy($file, "ssh2.sftp://{$resSFTP}/".$path)) {
                dump($file.' 上传失败');
            }else{
                dump($file.' 上传成功');
            }

        }else{
            dump($file.' 无法在服务器进行身份验证');
        }
        return true;

    }


    public function createdir($path){
        if (is_dir($path)){  //判断目录存在否，存在不创建
            return true;
        }else{ //不存在创建
            $re=mkdir($path,0755,true); //第三个参数为true即可以创建多极目录
            if ($re){
                return true;
            }else{
                return false;
            }
        }
    }

    public function createFilePath($dateType){
        $file_name = 'COL_'.$dateType.'_'.date('YmdHis');//数据类型_日期_大小.CSV
        $tmpFilePath = $this->tmplocalPath . '/'.$dateType;
        $this->createdir($tmpFilePath);
        $tmpFile = $tmpFilePath . "/".$file_name.'.csv';
        $outFilePath = $this->outlocalPath . '/'.$dateType;
        $this->createdir($outFilePath);
        return array($tmpFilePath,$outFilePath,$file_name);
    }

    public function timediff($starttime,$endtime)
    {
        //计算天数
        $timediff = $endtime-$starttime;
        $days = intval($timediff/86400);
        //计算小时数
        $remain = $timediff%86400;
        $hours = intval($remain/3600);
        //计算分钟数
        $remain = $remain%3600;
        $mins = intval($remain/60);
        //计算秒数
        $secs = intval(ceil($remain/60/60));
        $mins = $mins + $secs;
        $res = array("day" => $days,"hour" => $hours,"min" => $mins);
        return $res;
    }




}