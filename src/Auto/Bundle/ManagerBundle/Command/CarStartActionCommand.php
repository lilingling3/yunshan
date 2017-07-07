<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/10/13
 * Time: 下午2:14
 */
namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CarStartActionCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:carstart:action')
            ->setDescription('car start action')
            ->addOption('id', 'id', InputOption::VALUE_REQUIRED, 'car start id')
            ->addOption('action', 'o', InputOption::VALUE_REQUIRED, 'action option (open close gps status)')
            ->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'debug')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $id = $input->getOption('id');
        $operate = $input->getOption('action');

        $service_port = 40001;
        $address = 'carstart.lecarx.com';

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_nonblock($socket);

        if ($socket < 0) {

        }else {

        }
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 10,'usec' => 0));
        $result = socket_connect($socket, $address, $service_port);


        if($operate =='open'){

            $code = "SEND_CMD|京L12345|123456|".$id."|CMD13_00~NG|||||#";//解锁
        }

        if($operate =='close'){

            $code = "SEND_CMD|京L12345|123456|".$id."|CMD12_00~NG|||||#";//锁车
        }

        if($operate =='gps'){

            $code = "SEND_CMD|京L12345|123456|".$id."|CMD20_00~NG|||||#";//gps
        }

        if($operate =='status'){

            $code = "SEND_CMD|京L12345|123456|".$id."|CMD06~|||||#";
        }

        if($operate =='on'){

            $code = "SEND_CMD|京L12345|123456|".$id."|CMD17_00~NG|||||#";
        }

        if($operate =='off'){

            $code = "SEND_CMD|京L12345|123456|".$id."|CMD16_00~NG|||||#";
        }

        if($operate =='find'){

            $code ="SEND_CMD|京L12345|123456|".$id."|CMD14_00~NG|||||#";
        }

        if($operate =='restart'){

            $code ="SEND_CMD|京L12345|123456|".$id."|CMD26_00~NG|||||#";
        }

        if($operate =='encode'){

            $password = $input->getOption('password');
            if(!$password){

                echo 0;exit;

            }
            $code ="SEND_CMD|京L12345|123456|".$id."|CMD22_00~User~".$password."|||||#";
        }


        $start = "DENG_LU|京L12345|123456|".$id."||||#";   //初始化

        $this->send_socket($socket,$start);

        while($out = socket_read($socket, 8192)) {

            if($input->getOption('debug')==1){
                echo $out;
            }
            break;
        }

        $this->send_socket($socket,$code);

        while($out = socket_read($socket, 8192,PHP_NORMAL_READ)) {

            $str = iconv( 'GBK','UTF-8//IGNORE', $out);

            if($input->getOption('debug')==1){
                echo $str.PHP_EOL;
            }

            if($operate =='encode'&&strstr($str,"USER_KEY设置成功")){
                $output->writeln("1");
                break;
            }

            if($operate =='status'&&strstr($str,"状态")){
                $output->writeln("1");
                break;
            }
            if($operate =='close'&&strstr($str,"锁车动作已经执行")){
                $output->writeln("1");
                break;
            }

            if($operate =='open'&&strstr($str,"解锁动作已经执行")){
                $output->writeln("1");
                break;
            }

            if($operate =='find'&&strstr($str,"寻车动作已经执行")){
                $output->writeln("1");
                break;
            }

            if($operate =='on'&&strstr($str,"恢复油电路动作已经执行")){
                $output->writeln("1");
                break;
            }

            if($operate =='off'&&strstr($str,"切断油电路动作已经执行")){
                $output->writeln("1");
                break;
            }

            if($operate =='gps'&&strstr($str,"GPGGA")){
                $output->writeln("1");
                break;
            }

        }

        socket_close($socket);

    }

    public function send_socket($socket,$in){
        $in = iconv( 'UTF-8','GBK', $in);
        if(!socket_write($socket, $in, strlen($in))) {
        }else {
        }
    }

}