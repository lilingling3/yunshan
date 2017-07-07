<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/8/19
 * Time: 上午10:34
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Auto\Bundle\ManagerBundle\PushMessage\GpsData;

class YunGuanCommand  extends ContainerAwareCommand{

    public static $serverHost="zp790890874.oicp.net";
    public static $serverPort = "10240" ;
    public static $userId = "000003F3";    //用户名
    public static $password = "6A77746770730000" ;
    public static $head_flag = "5B";
    public static $msg_length = 0 ;
    public static $msg_sn = '00000000';
    public static $login = "1001";
    public static $logout = "1200";
    public static $keepLink = 0x1005;
    public static $close = 0x1008;
    public static $sendMsg = "1200" ;
    public static $gpsMsg = "1202" ;
    public static $msg_gnsscenterid = "00000481";   //接入码
    public static $version_flag = "01020F" ;
    public static $encrypt_flag = "00" ;
    public static $encrypt_key = "00000000" ;
    public static $down_link_ip = "3131352E3138322E3230302E3735000000000000000000000000000000000000";
    public static $down_link_port = '2800';
    //CRC
    public static $crc_code = "0481" ;
    public static $tail_flag = "5D";
    public static $car_color = "09";
    public static $car_msg = "1201";
    public static $platform_code = "00000000253";
    public static $produce_id = "00000000000";
    public static $terminal_model_type = "00000000";
    public static $terminal_id = "0000000";
    public static $terminal_simcode = "000000000000";
    public static $owers_id = "001540";



    public function configure()
    {
        $this
            ->setName('auto:yun:guan')
            ->setDescription('send tcp')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //login

        $temp = self::$msg_sn.self::$login.self::$msg_gnsscenterid.self::$version_flag.self::$encrypt_flag
            .self::$encrypt_key.self::$userId.self::$password.self::$down_link_ip.self::$down_link_port;

        $temp = str_replace('5E','5E02',$temp);
        $temp = str_replace('5D','5E01',$temp);
        $temp = str_replace('5A','5A02',$temp);
        $temp = str_replace('5B','5A01',$temp);

        $temp_length = dechex((strlen($temp)+16)/2);
        $temp_length = sprintf("%08d", $temp_length);

        $msg = self::$head_flag.$temp_length.$temp.self::$crc_code.self::$tail_flag;

        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $client->set(array(
            'open_tcp_nodelay'     =>  true,
        ));


//设置事件回调函数
        $client->on("connect", function($cli)use($msg) {

            $cli->send(pack("H*",$msg));

        });

        $client->on("receive", function($cli, $data){

            //upload car
            $code = substr(bin2hex($data),18,4);
            echo $code.PHP_EOL;

            if($code == '1002'){

                $car_messages = $this->upload_car();
                foreach($car_messages as $message){
                    $cli->send(pack("H*",$message));
                }

                swoole_timer_tick(60000, function ()use($cli) {

                    $messages = $this->upload_gps();
                    foreach($messages as $message){
                        echo $message.PHP_EOL;
                        usleep(50000);
                        
                        $cli->send(pack("H*",$message));
                    }


                });

            }

            if($code == '0000'){




            }

            if($code == '1006'){




            }

        });
        $client->on("error", function($cli){
            echo "Connect failed\n";
        });
        $client->on("close", function($cli){
            echo "Connection close\n";
        });
//发起网络连接
        $client->connect('zp790890874.oicp.net',29759, 0.5);




    }

    public function upload_car(){

        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $cars = $qb
            ->select('c')
            ->andWhere($qb->expr()->neq('c.company',':company1'))
            ->andWhere($qb->expr()->neq('c.company',':company2'))
            ->setParameter('company1', 2)
            ->setParameter('company2', 18)
            ->getQuery()
            ->getResult()
        ;



        foreach($cars as $car){

            echo '上传车辆信息'.$car->getLicense().PHP_EOL;
            $car_plate = iconv('UTF-8', 'GBK', $car->getLicense());
            $car_msg = bin2hex(self::$platform_code).bin2hex(self::$produce_id).bin2hex
                (self::$terminal_model_type).bin2hex(self::$terminal_id).bin2hex(self::$terminal_simcode).bin2hex
                (self::$owers_id);

            $car_length = str_pad(dechex(strlen($car_msg)/2),8,0,STR_PAD_LEFT);

            $msg = self::$msg_sn.self::$sendMsg.self::$msg_gnsscenterid.self::$version_flag
                .self::$encrypt_flag.self::$encrypt_key.str_pad(bin2hex($car_plate),42,0,STR_PAD_RIGHT)
                .self::$car_color.self::$car_msg;

            $string = strtoupper($msg.$car_length.$car_msg);
            $string = $this->replace_str($string);

            $string_length = strtoupper(str_pad(dechex((strlen($string)+16)/2),8,0,STR_PAD_LEFT));
            $string_length = $this->replace_str($string_length);

            $car_upload[] = self::$head_flag.$string_length.$string.self::$crc_code.self::$tail_flag;
        }
        return $car_upload;

    }

    public function logout(){

        $logout_string = self::$msg_sn.self::$logout.self::$msg_gnsscenterid.self::$version_flag
            .self::$encrypt_flag
            .self::$encrypt_key.self::$userId.self::$password;

        $logout_string = strtoupper($logout_string);

        $logout_string = $this->replace_str($logout_string);

        $logout_length = strtoupper(str_pad(dechex((strlen($logout_string)+16)/2),8,0,STR_PAD_LEFT));

        $logout_length = $this->replace_str($logout_length);


        return strtoupper(self::$head_flag.$logout_length.$logout_string.self::$crc_code.self::$tail_flag);
    }

    public function upload_gps(){

        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $cars = $qb
            ->select('c')
            ->andWhere($qb->expr()->neq('c.company',':company1'))
            ->andWhere($qb->expr()->neq('c.company',':company2'))
            ->setParameter('company1', 2)
            ->setParameter('company2', 18)
            ->getQuery()
            ->getResult()
        ;

        foreach($cars as $car){
            echo '上传GPS信息'.$car->getLicense().PHP_EOL;

            $redis = $this->getContainer()->get('snc_redis.default');

            $redis_cmd= $redis->createCommand('lindex',array($car->getDeviceCompany()->getEnglishName().'-gps-'
            .$car->getBoxId(),0));
            $gps_json = $redis->executeCommand($redis_cmd);
            $gps_arr = json_decode($gps_json,true);


            $gps_data  = new \Auto\Bundle\ManagerBundle\PushMessage\GpsData();
            $gps_data->setCarNumber(iconv('UTF-8', 'GBK', $car->getLicense()));
            $gps_data->setDeviceId("123456");
            $gps_data->setGoodsId(123456);
            $gps_data->setGpsTime(new \DateTime(date('YmdHis',$gps_arr['time'])));
            $gps_data->setId(123456);
            $gps_data->setLatitude($gps_arr['coordinate'][1]);
            $gps_data->setLongitude($gps_arr['coordinate'][0]);

            $dd = str_pad(dechex(date('d',$gps_data->getGpsTime()->getTimestamp())),2,0,STR_PAD_LEFT);
            $mm = str_pad(dechex(date('m',$gps_data->getGpsTime()->getTimestamp())),2,0,STR_PAD_LEFT);
            $yy = str_pad(dechex(date('Y',$gps_data->getGpsTime()->getTimestamp())),4,0,STR_PAD_LEFT);

            $hh = str_pad(dechex(date('h',$gps_data->getGpsTime()->getTimestamp())),2,0,STR_PAD_LEFT);
            $ii = str_pad(dechex(date('i',$gps_data->getGpsTime()->getTimestamp())),2,0,STR_PAD_LEFT);
            $ss = str_pad(dechex(date('s',$gps_data->getGpsTime()->getTimestamp())),2,0,STR_PAD_LEFT);


            $lat = str_pad(dechex($gps_data->getLatitude()*1000000),8,0,STR_PAD_LEFT);
            $lng = str_pad(dechex($gps_data->getLongitude()*1000000),8,0,STR_PAD_LEFT);

            $vec1 = str_pad(dechex(60),4,0,STR_PAD_LEFT);
            $vec2 = str_pad(dechex(70),4,0,STR_PAD_LEFT);
            $vec3 = str_pad(dechex(80),8,0,STR_PAD_LEFT);

            $direction =str_pad(dechex(60),4,0,STR_PAD_LEFT);
            $altitude =str_pad(dechex(60),4,0,STR_PAD_LEFT);//海拔
            $state = str_pad(dechex(0),8,0,STR_PAD_LEFT);
            $alarm = str_pad(dechex(0),8,0,STR_PAD_LEFT);

            $gps_string =bin2hex(0).$dd.$mm.$yy.$hh.$ii.$ss.$lng.$lat.$vec1.$vec2.$vec3.$direction.$direction.
                $altitude.$state.$alarm;

            $gps_string_length = str_pad(dechex(strlen($gps_string)/2),8,0,STR_PAD_LEFT);





            $msg_string = self::$msg_sn.self::$sendMsg.self::$msg_gnsscenterid.self::$version_flag
                .self::$encrypt_flag
                .self::$encrypt_key.str_pad(bin2hex($gps_data->getCarNumber()),42,0,STR_PAD_RIGHT).self::$car_color
                .self::$gpsMsg.$gps_string_length.$gps_string;


            $msg_string = strtoupper($msg_string);

            $msg_string = $this->replace_str($msg_string);


            $msg_length = strtoupper(str_pad(dechex((strlen($msg_string)+16)/2),8,0,STR_PAD_LEFT));

            $msg_length = $this->replace_str($msg_length);

            $arr[] =  strtoupper(self::$head_flag.$msg_length.$msg_string.self::$crc_code.self::$tail_flag);

        }

        return $arr;
    }


    public function replace_str($string){

        $string = str_replace('5E','5E02',$string);
        $string = str_replace('5D','5E01',$string);
        $string = str_replace('5A','5A02',$string);
        $string = str_replace('5B','5A01',$string);
        return $string;

    }

}