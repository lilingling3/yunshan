<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/9/9
 * Time: 下午3:14
 */
namespace Auto\Bundle\ManagerBundle\Helper;


use Symfony\Component\Validator\Constraints\DateTime;

class PaymentHelper extends AbstractHelper{

    //推送信息
    const PUSH_PAYMENT_MESSAGE = '1';

    //添加用户消息
    const ADD_PAYMENT_MESSAGE = '2';

    public function get_payment_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\PaymentOrder $p) {



            $payment_order = [
                'paymentOrderId'                => $p->getId(),
                'amount'                        => $p->getAmount(),
                'payTime'                       => $p->getPayTime()?$p->getPayTime()->format('Y/m/d H:i'):null,
                'createTime'                    => $p->getCreateTime()?$p->getCreateTime()->format('Y/m/d H:i'):null,
                'kind'                          => !empty($p->getKind())?$this->globalHelper->get_payment_order_kind_data($p->getKind()):null,
                'reason'                        => $p->getReason(),
            ];

            return $payment_order;
        };
    }




    //添加缴费信息后推送缴费信息
    public function pushPaymentMessage(\Auto\Bundle\ManagerBundle\Entity\PaymentOrder $p){

        $qb = $this->em->createQueryBuilder();

        $device =
            $qb
                ->select('d')
                ->from('AutoManagerBundle:MobileDevice', 'd')
                ->where($qb->expr()->eq('d.member', ':member'))
                ->setParameter('member', $p->getMember())
                ->orderBy('d.createTime', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if(!empty($device)) {

                //给对应设备发推送缴费相关信息

                $message = $this->getMessage(self::PUSH_PAYMENT_MESSAGE);
                $subject = 9;

                if ($device->getPlatform() == \Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_PLATFORM_IOS) {

                    $this->pushHelper->pushTokenIos($message, $device->getDeviceToken(), ['subject' => $subject]);

                } else {

                    $this->pushHelper->pushTokenAndroid($message, $device->getDeviceToken(), ['subject' => $subject]);

                }



        }


    }


    public function getMessage($type){


        if($type == self::PUSH_PAYMENT_MESSAGE){

            return $message = '您有一条缴费信息，为了不影响您正常使用租车服务，请及时处理。';

        }

        if($type == self::ADD_PAYMENT_MESSAGE){

            return $message = '您有一条缴费信息，为了不影响您正常使用租车服务，请及时处理。';

        }


    }




    public function setGlobalHelper($globalHelper){

        $this->globalHelper = $globalHelper;

    }

    public function setPushHelper($pushHelper)
    {
        $this->pushHelper = $pushHelper;
    }

}