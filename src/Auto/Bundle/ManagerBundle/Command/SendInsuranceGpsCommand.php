<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/6/15
 * Time: 下午5:20
 */
namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SendInsuranceGpsCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('auto:send:insurance:gps')
            ->setDescription('send insurance gps while pay insurance');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


      #  swoole_timer_tick(20000, function ($timer_id) {

            $this->sendInsuranceGps();

     #   });
    }


    /**
     * 
     */
    public function sendInsuranceGps()
    {

        // 找未完成的保险

        $qb = $this->getContainer()->get('doctrine')->getManager();

        $insuranceList =
            $qb
                ->getRepository('AutoManagerBundle:InsuranceRecord')
                ->findBy(['endTime'=>null]);

        $list = [];


        $redis = $this->getContainer()->get('snc_redis.default');

        foreach ($insuranceList as $k => $v) {


            $deviceNum = $v->getRentalCar()->getBoxid();

            if (empty($deviceNum)) {

                continue;
            }

            $redis_cmd_gps= $redis->createCommand('hget',array("feeZu-car-curlocation",$deviceNum));
            $gps = json_decode($redis->executeCommand($redis_cmd_gps));

            $list[] = [
                        'policyNo' => (string)$v->getInsuranceNumber(),
                        'lon'      => (string)$gps[0],
                        'lat'      => (string)$gps[1],
                        'rcTime'   => (string)time(),
                    ];

        }
        
        if (!empty($list)) {
            $bizContent = [
                    'productNo' => 'TCL(ws)',
                    'gpsinfos'  => json_encode($list),
                    'coordstype'=> 'gps',
            ];

            $result = $this->getContainer()->get('auto_manager.insurance_helper')->reportgps($bizContent);
	    
	    dump($list);
        }

        
    }



}