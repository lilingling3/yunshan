<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/10/11
 * Time: 下午3:52
 */
namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AddActivityDataToRedisCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('auto:add:activity:data:to:redis')
            ->setDescription('add activity data to redis');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


            $this->saveDataToRedis();


    }



    /**
     * 将活动相关数据存入redis
     */

    public function saveDataToRedis()
    {

        $redis = $this->getContainer()->get('snc_redis.default');

        $path = dirname(__FILE__);

        $temp=file($path."/test.csv");//连接EXCEL文件,格式为了.csv


        for ($i=1;$i <count($temp);$i++)
        {
            $string=explode(",",$temp[$i]);//通过循环得到EXCEL文件中每行记录的值

            $id = $i-1;

            $activity_data =['id'=>$id,'code'=>$string[2]];

            $redis_data = $redis->createCommand('lpush', array("sport_member_code", json_encode( $activity_data)));

            $redis->executeCommand($redis_data);


        }

    }




}