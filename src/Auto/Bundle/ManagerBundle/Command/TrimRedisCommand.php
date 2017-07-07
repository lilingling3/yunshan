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


class TrimRedisCommand extends ContainerAwareCommand
{


    const REDIS_ALL = 0;            // 全部数据
    const REDIS_GPS_DATA = 1;       // GPSs数据
    const REDIS_RANGE_DATA = 2;     // 剩余里程
    const REDIS_MILEAGE_DATA = 3;   // 总里程数据
    const REDIS_POWER_DATA = 4;     // 电量数据


    public function configure()
    {
        $this
            ->setName('release:redis:memory')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'type')
            ->setDescription('Flushes the feeZu redis database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $redis = $this->getContainer()->get('snc_redis.default');

        $releaseType = $input->getOption('type');

        $redisDataType = "";

        if (empty($releaseType)) {
            
            // 全部redis数据类型
            $typeList = ['gps','power','range','mileage'];

            foreach ($typeList as $type) {

                // 找出同一类的key
                $redis_cmd = $redis->createCommand('keys',array("feeZu-". $type ."-*"));
                $list = $redis->executeCommand($redis_cmd);

                foreach ($list as $key => $value) {
                    
                    $redis_cmd = $redis->createCommand('llen',array($value));
                    $llen = $redis->executeCommand($redis_cmd);

                    if ($llen >= 10000) {
                        
                        // 对超过10000条的数据进行裁剪
                        $redis_cmd = $redis->createCommand('ltrim',array($value, 0, 10000));
                        $redis->executeCommand($redis_cmd);
                    }
                }
            }

        } else if($releaseType == self::REDIS_GPS_DATA) {
            $redisDataType = "gps";
            
        } else if($releaseType == self::REDIS_RANGE_DATA) {
            $redisDataType = "range";

        } else if($releaseType == self::REDIS_MILEAGE_DATA) {
            $redisDataType = "mileage";

        } else if($releaseType == self::REDIS_POWER_DATA) {
            $redisDataType = "power";

        } else {
            echo "The option does not exist."; 
            exit;
        }


        // 对特定某类数据进行裁剪
        $redis_cmd = $redis->createCommand('keys',array("feeZu-". $redisDataType ."-*"));

        $list = $redis->executeCommand($redis_cmd);

        foreach ($list as $key => $value) {
            
            $redis_cmd = $redis->createCommand('llen',array($value));
            $llen = $redis->executeCommand($redis_cmd);

            if ($llen >= 10000) {
                
                $redis_cmd = $redis->createCommand('ltrim',array($value, 0, 10000));
                $redis->executeCommand($redis_cmd);
            }
        }
    }
}
