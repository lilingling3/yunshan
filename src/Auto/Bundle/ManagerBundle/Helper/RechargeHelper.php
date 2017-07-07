<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/9
 * Time: 上午10:19
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class RechargeHelper extends AbstractHelper{

    public function get_recharge_activity_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\RechargeActivity $m) {

                    $recharge_activity_list = [
                        'activity'      => $m->getId(),
                        'amount'        => $m->getAmount(),
                        'name'          => $m->getName(),
                        'discountAmount'=> $m->getAmount()*($m->getDiscount()-1),
                        'startTime'     => $m->getStartTime()->format('Y-m-d'),
                        'endTime'       => $m->getEndTime()->format('Y-m-d'),

                    ];


            return $recharge_activity_list;
        };
    }


    public function get_recharge_price_step_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\RechargePriceStep $m) {

                    $recharge_price_step_list = [
                        // 'step'          => $m->getStep(),
                        'amount'            => empty($m->getPrice()) ? 0:$m->getPrice(),
                        'discountAmount'    => empty($m->getCashBack())? 0:$m->getCashBack()
                    ];


            return $recharge_price_step_list;
        };
    }

    public function get_current_activity()
    {
        $today =new \DateTime();


        $qb = $this->em->createQueryBuilder();

        $activity =
            $qb
                ->select('r')
                ->from('AutoManagerBundle:RechargeActivity', 'r')
                ->orderBy('r.id', 'DESC')
                ->andWhere($qb->expr()->lte('r.startTime', ':today'))
                ->andWhere($qb->expr()->gte('r.endTime', ':today'))
                ->setParameter('today', $today)
                ->getQuery()
                ->getOneorNullResult()
        ;

        return empty($activity) ? 1: $activity->getId();

    }


    public function get_recharge_record()
    {

        return function (\Auto\Bundle\ManagerBundle\Entity\RechargeRecord $m) {

                    $recharge_record_list = [
                        'recordId'          => $m->getId(),
                        'mobile'            => $m->getMember()->getMobile(),
                        'refundAmount'      => empty($m->getRechargeOrder())? 0:$m->getRechargeOrder()->getActualRefundAmount(),
                        'chargeAmount'      => empty($m->getRechargeOrder())? 0:$m->getRechargeOrder()->getRefundAmount(),
                        'operate'           => $m->getOperate()->getName(),
                        'operateID'         => $m->getOperate()->getID(),
                        'operator'          => empty($m->getOperater())? null: $m->getOperater()->getName(),
                        'operatorMobile'    => empty($m->getOperater())? null: $m->getOperater()->getMobile(),
                        'amount'            => empty($m->getRechargeOrder()) ? number_format($m->getAmount(),2, '.', '') : $m->getRechargeOrder()->getRefundAmount(),
                        'createTime'        => empty($m->getCreateTime())? null:$m->getCreateTime()->format('Y/m/d H:i:s'),
                        'refundTime'        => empty($m->getRechargeOrder())? 0:$m->getRechargeOrder()->getRefundTime()->format('Y/m/d H:i:s'),
                        'wallet'            => number_format($m->getWalletAmount(),2, '.', ''),
                        'remark'            => $m->getRemark(),
                    ];


            return $recharge_record_list;
        };

    }
    //1.4 判断余额操作状态
    public function balance_record ($operate,$memberId,$actualAmount,$amount,$consumptionAmount)
    {
        //1.4余额记录
        //查找用户最新一条余额变动记录
        $qb = $this->em->createQueryBuilder();

        $BalanceArr =
            $qb
                ->select('b')
                ->from('AutoManagerBundle:BalanceRecord', 'b')
                ->andWhere($qb->expr()->eq('b.memberId', ':memberId'))
                ->setParameter('memberId', $memberId)
                ->orderBy('b.rechargeStartTime', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getResult()
        ;
        $Balance = $BalanceArr[0];
        //var_dump($Balance);die;
        $man = $this->em;

        //无此用户数据时禁止的操作
        $operateArr = array(3,4,5);
        //1充值返现 2人工充值 3租车费用 4退款 5扣款 6邀请返现

        //若无此用户数据或者用户已退款完毕则新添数据
        if(empty($Balance) || 303 == $Balance->getStatus()){
            //没有此用户数据时禁止 3 4 5
            if(in_array($operate,$operateArr)){
                echo '无此用户数据禁止"3租车费用 4退款 5扣款"';die;
            }
            $BalanceRecord = new \Auto\Bundle\ManagerBundle\Entity\BalanceRecord();

            $BalanceRecord->setMemberId($memberId);
            $BalanceRecord->setActualAmount($actualAmount);
            $BalanceRecord->setAmount($amount);
            $BalanceRecord->setConsumptionAmount($consumptionAmount);
            $BalanceRecord->setRechargeStartTime(new \DateTime());
            $BalanceRecord->setRefundTime(NULL);
            $BalanceRecord->setStatus(301);

            $man->persist($BalanceRecord);
            $man->flush();
        }else{
            //若是退款
            if(4 == $operate){
                $consumptionAmount = $Balance->getConsumptionAmount() + $consumptionAmount;

                $Balance->setConsumptionAmount($consumptionAmount);
                $Balance->setStatus(302);

                $man->persist($Balance);
                $man->flush();
            }else{
                if(301 == $Balance->getStatus()){
                    //要更新的数据
                    $actualAmount = $Balance->getActualAmount() + $actualAmount;
                    $amount = $Balance->getAmount() + $amount;
                    $consumptionAmount = $Balance->getConsumptionAmount() + $consumptionAmount;

                    $Balance->setActualAmount($actualAmount);
                    $Balance->setAmount($amount);
                    $Balance->setConsumptionAmount($consumptionAmount);

                    $man->persist($Balance);
                    $man->flush();

                }elseif(302 == $Balance->getStatus()){
                    echo '退款期间不可操作余额';die;
                }
            }
        }
    }


}