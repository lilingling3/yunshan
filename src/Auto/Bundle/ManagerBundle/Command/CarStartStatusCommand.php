<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/11/12
 * Time: 下午5:00
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;


class CarStartStatusCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:carstart:status')
            ->setDescription('save car start status to redis')
        ;
    }

    public function get_number($str,$mark){

        $start = strpos($str,$mark);

        $str = substr($str,$start);

        $str = str_replace($mark,'',$str);

        $char = '';

        $string = '';

        for($i=0;$i<strlen($str);$i++){

            $char = substr($str,$i,1);

            if(is_numeric($char)){

                $string.=$char;

            }else{
                break;
            }

        }
        return $string;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
//设置事件回调函数
        $client->on("connect", function($cli) {

        });
        $client->on("receive", function($cli, $data){


            $redis = $this->getContainer()->get('snc_redis.default');

            echo  $data = iconv('GBK', 'UTF-8', $data);

            $data_arr = explode("|",$data);

            if(strstr($data_arr[2],"状态")){

                $begin = strpos($data_arr[2],'[');

                $status = substr($data_arr[2],$begin+1,4);
                echo '状态:'.$status.PHP_EOL;

                $arr = ['id'=>$data_arr[0],'status'=>$status,'time'=>(new \DateTime())->getTimestamp()];

                $redis_cmd= $redis->createCommand('lpush',["carStart-status-".$data_arr[0],json_encode($arr)]);
                $redis->executeCommand($redis_cmd);


            }

            if(strstr($data,"锁车动作已经执行")){

                echo '锁车'.PHP_EOL;
                $arr = ['id'=>$data_arr[0],'lock'=>1,'time'=>(new \DateTime())->getTimestamp()];

                $redis_cmd= $redis->createCommand('lpush',["carStart-door-".$data_arr[0],json_encode($arr)]);
                $redis->executeCommand($redis_cmd);

            }

            if(strstr($data,"解锁动作已经执行")){
                echo '解锁'.PHP_EOL;
                $arr = ['id'=>$data_arr[0],'lock'=>0,'time'=>(new \DateTime())->getTimestamp()];

                $redis_cmd= $redis->createCommand('lpush',["carStart-door-".$data_arr[0],json_encode($arr)]);
                $redis->executeCommand($redis_cmd);
            }


            if(strstr($data,"寻车动作已经执行")){
                echo '寻车'.PHP_EOL;

                $arr = ['id'=>$data_arr[0],'time'=>(new \DateTime())->getTimestamp()];
                $redis_cmd= $redis->createCommand('lpush',["carStart-find-".$data_arr[0],json_encode($arr)]);
                $redis->executeCommand($redis_cmd);
            }

            if(strstr($data_arr[2],"总里程")){

                $mileage = $this->get_number($data_arr[2],'总里程=[');
                if(intval($mileage)>0){
                    $arr = ['id'=>$data_arr[0],'mileage'=>$mileage,'time'=>(new \DateTime())->getTimestamp()];

                    $redis_cmd= $redis->createCommand('lpush',["carStart-mileage-".$data_arr[0],json_encode($arr)]);
                    $redis->executeCommand($redis_cmd);
                    echo '里程'.$mileage.PHP_EOL;
                }

            }

            if(strstr($data_arr[2],"SOC=[")){

                $power = $this->get_number($data_arr[2],'SOC=[');
                if(intval($power)>0){
                    $arr = ['id'=>$data_arr[0],'power'=>$power,'time'=>(new \DateTime())->getTimestamp()];


                    $redis_cmd= $redis->createCommand('lpush',["carStart-power-".$data_arr[0],json_encode($arr)]);
                    $redis->executeCommand($redis_cmd);
                    echo '电量'.$power.PHP_EOL;
                }

            }

            if(strstr($data_arr[2],"续航里程=[")){

                $range = $this->get_number($data_arr[2],'续航里程=[');
                if(intval($range)>0){
                    $arr = ['id'=>$data_arr[0],'range'=>$range,'time'=>(new \DateTime())->getTimestamp()];

                    $redis_cmd= $redis->createCommand('lpush',["carStart-range-".$data_arr[0],json_encode($arr)]);
                    $redis->executeCommand($redis_cmd);
                    echo '续航里程'.$range.PHP_EOL;
                }

            }


            if(strstr($data,"GPGGA")){

                $latlng = $this->getlatlng($data);

                if(!empty($latlng)){

                    echo 'gps:'.$latlng[0].','.$latlng[1].PHP_EOL;
                    $arr = ['id'=>$data_arr[0],'coordinate'=>$latlng,'time'=>(new \DateTime())->getTimestamp()];

                    $redis_cmd= $redis->createCommand('lpush',["carStart-gps-".$data_arr[0],json_encode($arr)]);
                    $redis->executeCommand($redis_cmd);
                }
            }

        });
        $client->on("error", function($cli){
            echo "Connect failed\n";
        });
        $client->on("close", function($cli){
            echo "Connection close\n";
        });
//发起网络连接
        $client->connect("xxxxx", 50001, 0.5);
    }


    public function getlatlng($out){

        $out_arr = explode("|",$out);
        $gps_str = $out_arr[2];
        $gps_arr = explode(',',$gps_str);

        //PC_CLIENT|RCVD_DEV|$GPGGA,113122.000,3956.0398,N,11629.1983,E,2,9,1.03,33.4,M,-5.8,M,0000,0000*77|35FFD8054342363828552543|#

        if(isset($gps_arr[3])&&$gps_arr[3]=='N'&&isset($gps_arr[5])&&$gps_arr[5]=='E'){

            $lat = $gps_arr[2];
            $lng =  $gps_arr[4];
            $latitude = substr($lat,0,2)+substr($lat,2)/60;
            $longitude = substr($lng,0,3)+substr($lng,3)/60;

            if($longitude<=135 && $longitude>=70&&$latitude>4&&$latitude<54 ){

                $latitude = substr($lat,0,2)+substr($lat,2)/60+0.001233666667;
                $longitude = substr($lng,0,3)+substr($lng,3)/60+0.005909;

                return [$longitude,$latitude];

            }else{
                return [];
            }



        }else{
            return [];
        }
    }

}