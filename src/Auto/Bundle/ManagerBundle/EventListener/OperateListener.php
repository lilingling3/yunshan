<?php
namespace Auto\Bundle\ManagerBundle\EventListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Auto\Bundle\ManagerBundle\Entity\LicensePlace;
use Auto\Bundle\ManagerBundle\Entity\Area;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Auto\Bundle\ManagerBundle\Entity\Color;
use Auto\Bundle\ManagerBundle\Entity\Car;
use Auto\Bundle\ManagerBundle\Entity\SMS;
use Auto\Bundle\ManagerBundle\Entity\Company;
use Auto\Bundle\ManagerBundle\Entity\CarLevel;
use Auto\Bundle\ManagerBundle\Entity\CouponKind;
use Auto\Bundle\ManagerBundle\Entity\BodyType;
use Auto\Bundle\ManagerBundle\Entity\Station;
use Auto\Bundle\ManagerBundle\Entity\RentalPrice;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Entity\Member;
use Auto\Bundle\ManagerBundle\Entity\CouponActivity;
use Auto\Bundle\ManagerBundle\Entity\Coupon;
use Auto\Bundle\ManagerBundle\Entity\BlackList;
use Auto\Bundle\ManagerBundle\Entity\Appeal;
use Auto\Bundle\ManagerBundle\Entity\RechargeOrder;
use Auto\Bundle\ManagerBundle\Entity\RefundRecord;

use Auto\Bundle\ManagerBundle\Entity\Operator;
class OperateListener
{
    private $container;
    const CREATE_ENTITY = 1;  //创建
    const UPDATE_ENTITY = 2;  //更新
    const REMOVE_ENTITY = 3;  //删除

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $user = null !== $this->container->get('security.context')->getToken()?$this->container->get('security.context')->getToken()->getUser():null;
        $record = new \Auto\Bundle\ManagerBundle\Entity\OperateRecord();
        $entity = $args->getEntity();
        $man = $args->getEntityManager();
        $object_name = '';
        $content = '';
        if(!empty($user)){
            if($entity instanceof Area){
                $object_name = 'Area';
                $content = $entity->getName();
            }

            if($entity instanceof Color){
                $object_name = 'Color';
                $content = $entity->getName();
            }


            if($entity instanceof Car){
                $object_name = 'Car';
                $content = $entity->getName();
            }

            if($entity instanceof RentalCar){
                $object_name = 'RentalCar';
                $content = $entity->getLicense();
            }

            if($entity instanceof LicensePlace){
                $object_name = 'LicensePlace';
                $content = $entity->getName();
            }

            if($entity instanceof Station){
                $object_name = 'Station';
                $content = $entity->getName();
            }

            if($entity instanceof BodyType){
                $object_name = 'BodyType';
                $content = $entity->getName();
            }

            if($entity instanceof CarLevel){
                $object_name = 'CarLevel';
                $content = $entity->getName();
            }

            if($entity instanceof Company){
                $object_name = 'Company';
                $content = $entity->getName();
            }

            if($entity instanceof SMS){
                $object_name = 'SMS';
                $content = '至用户'.$entity->getMobile();
            }

            if($entity instanceof CouponKind){
                $object_name = 'CouponKind';
                $content = '至用户'.$entity->getName();
            }

            if($entity instanceof BlackList){
                $object_name = 'BlackList';
                $content = '拉黑'.$entity->getAuthMember()->getMember()->getName();
            }

            if($entity instanceof CouponActivity){
                $object_name = 'CouponActivity';
                $content = '至用户'.$entity->getName();
            }

            if($entity instanceof Appeal){
                $object_name = 'Appeal';
                $content = '申诉用户'.$entity->getBlackList()->getAuthMember()->getMember()->getName();
            }


            if($entity instanceof Coupon){
                $object_name = 'Coupon';
                $to = !empty($entity->getMember())?'至用户'.$entity->getMember()->getMobile():'';
                $code = !empty($entity->getCode())?$entity->getCode():'';
                $content = $entity->getActivity()->getName().$entity->getKind()->getName().$code.$to;
            }


            if($entity instanceof RentalPrice){
                $object_name = 'RentalPrice';
                $content = $entity->getCar()->getName().$entity->getName().$entity->getPrice();
            }

            if($entity instanceof RechargeOrder){
                if($entity->getPayTime() && $entity->getWechatTransactionId() == null && $entity->getAlipayTradeNo() == null ){
                    $object_name = 'RechargeOrder';
                    $content = $entity->getMember()->getMobile().'充值'.$entity->getAmount();
                }

            }

            if($entity instanceof RefundRecord){
                $object_name = 'RefundRecord';
                $content = '退款用户'.$entity->getMember()->getName().$entity->getMember()->getMobile();
            }

            if($object_name&&$content){

                $record->setObjectName($object_name);
                $record->setContent($content);
                $record->setObjectId($entity->getId());
                $record->setBehavior(self::CREATE_ENTITY);
                $record->setOperateMember($user);
                $man->persist($record);
                $man->flush();

            }
        }
    }

    public function postUpdate(LifecycleEventArgs $args){

        $user = null !== $this->container->get('security.context')->getToken()?$this->container->get('security.context')->getToken()->getUser():null;
        $record = new \Auto\Bundle\ManagerBundle\Entity\OperateRecord();
        $entity = $args->getEntity();
        $man = $args->getEntityManager();
        $object_name = '';
        $content = '';
        $authMeber=false;
        if(!empty($user)){

            if($entity instanceof AuthMember){
                $authMeber = true;
                if($entity->getAuthTime()){
                    $object_name = 'AuthMember';
                    $status = '失败';
                    if(
                        ($entity->getLicenseImageAuthError()==0)
                        && ($entity->getIdImageAuthError()==0)
                        && ($entity->getIdHandImageAuthError()==0)
                        && ($entity->getMobileCallError()==0)
                        && ( $entity->getValidateError()==0)
                    ){
                        if($entity->getSubmitType()==null){
                            $status = '图片成功';
                        }
                        else if($entity->getSubmitType()==1 && !empty($entity->getValidateNewResult())){
                            $status = '失败';
                        }
                        else{
                            $status = '成功';
                        }

                    }
                    else{
                        $status="图片失败";
                    }

                    $start=$entity->getApplyTime()?$entity->getApplyTime():$entity->getCreateTime();
                    $t=$entity->getAuthTime()->getTimestamp()-$start->getTimestamp();


                    $d = floor($t/3600/24);
                    $h = floor(($t%(3600*24))/3600);  //%取余
                    $m = floor(($t%(3600*24))%3600/60);
                    $s = floor(($t%(3600*24))%60);

                    $minutes=$d*24*60+$h*60+$m;
                    $authUseTime = "$minutes 分 $s 秒";

                    $content = '审核'.$entity->getMember()->getMobile().$status. '，用时：'.$authUseTime;
                }

            }
            if(!$authMeber && $entity instanceof Member ){
                if($entity->getRoles()){
                    $object_name = 'Member';
                    $content = '用户权限'.implode(',',$entity->getRoles());
                }
            }

            if($entity instanceof LicensePlace){
                $object_name = 'LicensePlace';
                $content = "test";
            }

            if($entity instanceof Area){
                $object_name = 'Area';
                $content = $entity->getName();
            }

            if($entity instanceof Station){
                $object_name = 'Station';
                $content = $entity->getName();
            }

            if($entity instanceof Car){
                $object_name = 'Car';
                $content = $entity->getName();
            }

            if($entity instanceof RentalCar){
                $object_name = 'RentalCar';
                $content = $entity->getLicense();
            }

            if($entity instanceof BodyType){
                $object_name = 'BodyType';
                $content = $entity->getName();
            }

            if($entity instanceof CarLevel){
                $object_name = 'CarLevel';
                $content = $entity->getName();
            }

            if($entity instanceof Color){
                $object_name = 'Color';
                $content = $entity->getName();
            }

            if($entity instanceof Company){
                $object_name = 'Company';
                $content = $entity->getName();
            }

            if($entity instanceof BlackList){
                $object_name = 'BlackList';
                $content = '移除'.$entity->getAuthMember()->getMember()->getName();
            }

            if($entity instanceof Appeal){
                $object_name = 'Appeal';
                $content = '申诉用户'.$entity->getBlackList()->getAuthMember()->getMember()->getName();
            }

            if($entity instanceof RentalPrice){
                $object_name = 'RentalPrice';
                $content = $entity->getCar()->getName().$entity->getName().$entity->getPrice();
            }


            if($entity instanceof RefundRecord){
                $object_name = 'RefundRecord';
                if($entity->getCheckFailedReason())
                {
                    $content = $entity->getMember()->getName().$entity->getMember()->getMobile().' 审核失败';
                }else
                {
                    $content = $entity->getMember()->getName().$entity->getMember()->getMobile().'审核成功,';
                    $rechargeOrders = $entity->getRechargeOrders();
                    $refunAmount = 0;
                    foreach ($rechargeOrders as $v) {
                        $refunAmount += $v->getActualRefundAmount();
                    }
                    $content .= ' 退款金额'.$refunAmount.'元';
                }
            }
            if($entity instanceof Operator){
                $object_name = 'Operator';
                $content =  "运营权限设置";
            }

            if($entity instanceof RechargeOrder){
                if($entity->getRefundTime()){
                    $object_name = 'RefundRecord';
                    if($entity->getWechatTransactionId())
                    {
                        $content = $entity->getMember()->getName().$entity->getMember()->getMobile().'执行退款,微信'.$entity->getWechatTransactionId().'退款'.$entity->getActualRefundAmount().'元，账号扣除'.$entity->getRefundAmount();
                    }elseif($entity->getAlipayTradeNo())
                    {
                        $content = '对'.$entity->getMember()->getName().$entity->getMember()->getMobile().'执行退款,支付宝'.$entity->getAlipayTradeNo().'退款'.$entity->getActualRefundAmount().'元，账号扣除'.$entity->getRefundAmount();
                    }
                }
            }


            if($object_name&&$content){

                $record->setObjectName($object_name);
                $record->setContent($content);
                $record->setObjectId($entity->getId());
                $record->setBehavior(self::UPDATE_ENTITY);
                $record->setOperateMember($user);
                $man->persist($record);
                $man->flush();

            }
        }

    }

    public function postRemove(LifecycleEventArgs $args){

        $user = null !== $this->container->get('security.context')->getToken()?$this->container->get('security.context')->getToken()->getUser():null;
        $record = new \Auto\Bundle\ManagerBundle\Entity\OperateRecord();
        $entity = $args->getEntity();
        $man = $args->getEntityManager();
        $object_name = '';
        $content = '';
        if(!empty($user)){

            if($entity instanceof Area){
                $object_name = 'Area';
                $content = $entity->getName();
            }

            if($entity instanceof LicensePlace){
                $object_name = 'LicensePlace';
                $content = $entity->getName();
            }

            if($entity instanceof Station){
                $object_name = 'Station';
                $content = $entity->getName();
            }

            if($entity instanceof Car){
                $object_name = 'Car';
                $content = $entity->getName();
            }
            if($entity instanceof RentalCar){
                $object_name = 'RentalCar';
                $content = $entity->getLicense();
            }

            if($entity instanceof BodyType){
                $object_name = 'BodyType';
                $content = $entity->getName();
            }

            if($entity instanceof CarLevel){
                $object_name = 'CarLevel';
                $content = $entity->getName();
            }

            if($entity instanceof Color){
                $object_name = 'Color';
                $content = $entity->getName();
            }

            if($entity instanceof Company){
                $object_name = 'Company';
                $content = $entity->getName();
            }

            if($entity instanceof RentalPrice){
                $object_name = 'RentalPrice';
                $content = $entity->getCar()->getName().$entity->getName().$entity->getPrice();
            }
            if($entity instanceof AuthMember){
                $object_name = 'AuthMember';
                $content = '重置认证信息'.$entity->getMember()->getMobile();
            }

            if($object_name&&$content){

                $record->setObjectName($object_name);
                $record->setContent($content);
                $record->setObjectId($entity->getId());
                $record->setBehavior(self::REMOVE_ENTITY);
                $record->setOperateMember($user);
                $man->persist($record);
                $man->flush();

            }
        }
    }
}