<?php
/**
 * Created by PhpStorm.
 * User: liyandong
 * Date: 16/6/2
 * Time: 17:39
 * 各种总数统计
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StatisticsAmountCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:statistics:amount')
            ->setDescription('statistics amount')
            ->addOption('day', 'day', InputOption::VALUE_OPTIONAL, 'day')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set("PRC");
        $date = date('Y-m-d',strtotime("-1 day"));
        $day = $input->getOption('day');
        if($day){
            $date = $day;
        }
        echo $date;
        $this->statisticsAmountHistory($date);
        dump( ' Amount OK ' );
//        for($i = 100; $i > 0; $i--){
//            $date = date('Y-m-d',strtotime("-$i day"));
//            $this->statisticsAmountHistory($date);
//        }
    }


    protected function statisticsAmountHistory($date){
        $datetime = $date.' 23:59:59';
        //dump($datetime);
        $man = $this->getContainer()->get('doctrine')->getManager();

        //查找记录是否已经存在
        $statisticsRecord=$man
            ->getRepository('AutoManagerBundle:StatisticsAmountRecord')
            ->findOneBy(['dateTime'=>new \DateTime($date)]);
        if($statisticsRecord){
            return true;
        }

        $qbm =
            $man
                ->getRepository('AutoManagerBundle:Member')
                ->createQueryBuilder('m');
        //注册用户总数
        $registMembers =$qbm->select($qbm->expr()->count('m'))
            ->where($qbm->expr()->lte('m.createTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();

        $qbam =$man
            ->getRepository('AutoManagerBundle:AuthMember')
            ->createQueryBuilder('am') ;
        //提交认证用户总数
        $authMembers = $qbam
             ->select($qbam->expr()->count('am'))
            ->where($qbm->expr()->lte('am.createTime', ':datetime'))
            ->setParameter('datetime', $datetime)
             ->getQuery()
             ->getSingleScalarResult();
        //认证通过用户总数
        $verifiedMembers = $qbam
            ->select($qbam->expr()->count('am'))
            ->where($qbm->expr()->lte('am.createTime', ':datetime'))
            ->andWhere($qbam->expr()->eq('am.licenseAuthError', ':licenseAuthError'))
            ->setParameter('datetime', $datetime)
            ->setParameter('licenseAuthError', 0)
            ->getQuery()
            ->getSingleScalarResult();

        $qbrco =$man
            ->getRepository('AutoManagerBundle:RechargeOrder')
            ->createQueryBuilder('rco') ;
        //充值用户总数
        $rechargeMembers = $qbrco
            ->select($qbrco->expr()->countDistinct('rco.member'))
            ->where($qbrco->expr()->isNotNull('rco.payTime'))
            ->andWhere($qbm->expr()->lte('rco.payTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();
        //实际充值总金额
        $actualRecharges = $qbrco
            ->select('sum(rco.actualAmount)')
            ->where($qbrco->expr()->isNotNull('rco.payTime'))
            ->andWhere($qbm->expr()->lte('rco.payTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();
        //充值后对应总金额
        $recharges = $qbrco
            ->select('sum(rco.amount)')
            ->where($qbrco->expr()->isNotNull('rco.payTime'))
            ->andWhere($qbm->expr()->lte('rco.payTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();


        $qbro =$man
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->createQueryBuilder('ro') ;
        //订单总数
        $orders = $qbro
            ->select($qbro->expr()->count('ro'))
            ->where($qbm->expr()->lte('ro.createTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();
        //已取消订单总数
        $cancelOrders = $qbro
            ->select($qbro->expr()->count('ro'))
            ->where($qbro->expr()->isNotNull('ro.cancelTime'))
            ->andWhere($qbm->expr()->lte('ro.cancelTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();
        //总完成订单应收费用（元）
        $dueAmount = $qbro
            ->select('sum(ro.dueAmount)')
            ->where($qbro->expr()->isNotNull('ro.payTime'))
            ->andWhere($qbm->expr()->lte('ro.payTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();
        //总完成订单减免费用（元）
        $reliefAmount = $qbro
            ->select('sum(ro.reliefAmount)')
            ->where($qbro->expr()->isNotNull('ro.payTime'))
            ->andWhere($qbm->expr()->lte('ro.payTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();
        //总完成订单实收费用（元）
        $actualAmount = $qbro
            ->select('sum(ro.amount)')
            ->where($qbro->expr()->isNotNull('ro.payTime'))
            ->andWhere($qbm->expr()->lte('ro.payTime', ':datetime'))
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getSingleScalarResult();
        //总完成订单优惠券抵用金额（元）
        $couponAmount = $dueAmount - $actualAmount - $reliefAmount;

        $arr = array(
            'date' => new \DateTime($date),
            'registMembers'=>0,
            'authMembers'=>0,
            'verifiedMembers'=>0,
            'rechargeMembers'=>0,
            'actualRecharges'=>0,
            'recharges'=>0,
            'orders'=>0,
            'cancelOrders'=>0,
            'dueAmount'=>0,
            'reliefAmount'=>0,
            'couponAmount'=>0,
            'actualAmount'=>0,
        );
        if($registMembers){
            $arr['registMembers'] = $registMembers;
        }
        if($verifiedMembers){
            $arr['authMembers'] = $authMembers;
        }
        if($verifiedMembers){
            $arr['verifiedMembers'] = $verifiedMembers;
        }
        if($rechargeMembers){
            $arr['rechargeMembers'] = $rechargeMembers;
        }
        if($actualRecharges){
            $arr['actualRecharges'] = $actualRecharges;
        }
        if($recharges){
            $arr['recharges'] = $recharges;
        }
        if($orders){
            $arr['orders'] = $orders;
        }
        if($cancelOrders){
            $arr['cancelOrders'] = $cancelOrders;
        }
        if($dueAmount){
            $arr['dueAmount'] = $dueAmount;
        }
        if($reliefAmount){
            $arr['reliefAmount'] = $reliefAmount;
        }
        if($actualAmount){
            $arr['actualAmount'] = $actualAmount;
        }
        if($couponAmount){
            $arr['couponAmount'] = $couponAmount;
        }
        $this->insertStatisticsAmountRecord($arr);
        return true;

    }

    /**
     * 插入统计记录
     * @param $arr
     */
    protected function insertStatisticsAmountRecord($arr){
        $man = $this->getContainer()->get('doctrine')->getManager();
        $StatisticsAmountRecord = new \Auto\Bundle\ManagerBundle\Entity\StatisticsAmountRecord;
        $StatisticsAmountRecord->setDateTime($arr['date']);
        if(isset($arr['registMembers'])){
            $StatisticsAmountRecord->setRegistMembers($arr['registMembers']);
        }
        if(isset($arr['authMembers'])){
            $StatisticsAmountRecord->setAuthMembers($arr['authMembers']);
        }
        if(isset($arr['verifiedMembers'])){
            $StatisticsAmountRecord->setVerifiedMembers($arr['verifiedMembers']);
        }
        if(isset($arr['rechargeMembers'])){
            $StatisticsAmountRecord->setRechargeMembers($arr['rechargeMembers']);
        }
        if(isset($arr['actualRecharges'])){
            $StatisticsAmountRecord->setActualRecharges($arr['actualRecharges']);
        }
        if(isset($arr['recharges'])){
            $StatisticsAmountRecord->setRecharges($arr['recharges']);
        }
        if(isset($arr['orders'])){
            $StatisticsAmountRecord->setOrders($arr['orders']);
        }
        if(isset($arr['cancelOrders'])){
            $StatisticsAmountRecord->setCancelOrders($arr['cancelOrders']);
        }
        if(isset($arr['dueAmount'])){
            $StatisticsAmountRecord->setDueAmount($arr['dueAmount']);
        }
        if(isset($arr['reliefAmount'])){
            $StatisticsAmountRecord->setReliefAmount($arr['reliefAmount']);
        }
        if(isset($arr['couponAmount'])){
            $StatisticsAmountRecord->setCouponAmount($arr['couponAmount']);
        }
        if(isset($arr['actualAmount'])){
            $StatisticsAmountRecord->setActualAmount($arr['actualAmount']);
        }
        $man->persist($StatisticsAmountRecord);
        $man->flush();
        return true;

    }


}