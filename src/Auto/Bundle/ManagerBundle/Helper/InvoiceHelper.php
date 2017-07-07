<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/27
 * Time: 下午4:56
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class InvoiceHelper extends AbstractHelper{

    public function get_invoice_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Invoice $i) {

            $invoice = [
                'invoiceID'                        => $i->getId(),
                'amount'                            =>$i->getAmount(),
                'title'                             =>$i->getTitle(),
                'deliveryName'                      =>$i->getDeliveryName(),
                'deliveryAddress'                   =>$i->getDeliveryAddress(),
                'deliveryMobile'                    =>$i->getDeliveryMobile(),
                'authStatus'                        =>$i->getAuthTime()
                    ?\Auto\Bundle\ManagerBundle\Entity\Invoice::AUTH_STATUS_SUCCESS: \Auto\Bundle\ManagerBundle\Entity\Invoice::AUTH_STATUS_FAIL,
                'deliveryStatus'                    =>$i->getDeliveryTime()
                    ?\Auto\Bundle\ManagerBundle\Entity\Invoice::DELIVERY_STATUS_SEND:\Auto\Bundle\ManagerBundle\Entity\Invoice::DELIVERY_STATUS_UNSENT,
                'deliveryCompany'                   =>!empty($i->getDeliveryCompany())?$i->getDeliveryCompany()
                    ->getName():'',
                'createTime'                        =>$i->getCreateTime()->format('Y/m/d'),
                'deliveryNumber'                    =>$i->getDeliveryNumber(),
                'serviceName'                       =>"车辆租赁服务"
            ];

            return $invoice;
        };
    }

}