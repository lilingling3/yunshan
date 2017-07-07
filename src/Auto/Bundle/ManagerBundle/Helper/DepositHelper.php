<?php
/**
 * Created by sublime.
 * User: luyao
 * Date: 16/9/9
 * Time: 下午3:14
 */
namespace Auto\Bundle\ManagerBundle\Helper;


use Symfony\Component\Validator\Constraints\DateTime;

class DepositHelper extends AbstractHelper{

    //推送信息
    const PUSH_PAYMENT_MESSAGE = '1';

    //添加用户消息
    const ADD_PAYMENT_MESSAGE = '2';

    public function get_deposit_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\DepositOrder $d) {

            $deposit_order = [
                    'orderID'                      => $d->getId(),
                    'mobile'                       => $d->getMember()->getMobile(),
                    'name'                         => $d->getMember()->getName(),
                    'createTime'                   => empty($d->getCreateTime())? null:$d->getCreateTime()->format('Y/m/d H:i:s'),
                    'payTime'                      => empty($d->getPayTime())? null:$d->getPayTime()->format('Y/m/d H:i:s'),
                    'endTime'                      => empty($d->getEndTime())? null:$d->getEndTime()->format('Y/m/d H:i:s'),
                    'refundTime'                   => empty($d->getRefundTime())? null:$d->getRefundTime()->format('Y/m/d H:i:s'),
                    'wechatTransactionId'          => $d->getWechatTransactionId(),
                    'alipayTradeNo'                => $d->getAlipayTradeNo(),
                    'refundAmount'                 => number_format($d->getRefundAmount(), 2),
                    'actualRefundAmount'           => number_format($d->getActualRefundAmount(), 2),
                    'wechatRefund'                 => $d->getWechatRefundId(),
                    'alipayRefundNo'               => $d->getAlipayRefundNo(),
                    'amount'                       => number_format($d->getAmount(), 2)
            ];

            return $deposit_order;
        };
    }

    public function get_deposit_info_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Deposit $d) {

            $deposit = [
                    'id'                            => $d->getId(),
                    'totalAmount'                   => number_format($d->getTotal(), 2),
                    'status'                        => $d->getStatus()
            ];

            return $deposit;
        };
    }

    public function get_deposit_area_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\DepositArea $d) {

            $deposit = [
                    'id'                            => $d->getId(),
                    'isneed'                        => $d->getIsNeed2Deposit(),
                    'amount'                        => number_format($d->getNeedDepositAmount(), 2),
            ];

            return $deposit;
        };
    }
}