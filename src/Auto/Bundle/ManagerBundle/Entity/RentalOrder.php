<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints;
/**
 * RentalOrder
 *
 * @ORM\Table()
 * @ORM\Entity
 * @Constraints\GroupSequence({"RentalOrder", "commit"})
 */
class RentalOrder extends BaseOrder
{
    const CANCEL_ORDER = 100;  //订单已取消
    const PROCESS_NO_TAKE_ORDER = 101;  //已下单未取车
    const PROCESS_HAS_TAKE_ORDER = 102;  //已取车未还车
    const BACK_NO_PAY_ORDER = 103;   //已下单已还车未付款
    const REFUND_ORDER = 104;   //退款订单
    const PAYED_ORDER = 199;    //已付款


    //source :::1：ios  2：安卓  3：微信公共号 4:微信小程序

    /**
     * @var RentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var PickUpStation
     *
     * @ORM\ManyToOne(targetEntity="RentalStation")
     */
    private $pickUpStation;

    /**
     * @var ReturnStation
     *
     * @ORM\ManyToOne(targetEntity="RentalStation")
     */
    private $returnStation;

    /**
     * @var Coupon
     *
     * @ORM\ManyToOne(targetEntity="Coupon")
     */
    private $coupon;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string",nullable=true, length=11)
     */
    private $mobile;

    /**
     * @var Partner
     *
     * @ORM\ManyToOne(targetEntity="Partner")
     */
    private $partner;

    /**
     * @var string
     *
     * @ORM\Column(name="wechatTransactionId", type="string",nullable=true)
     */
    private $wechatTransactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="alipayTradeNo", type="string",nullable=true)
     */
    private $alipayTradeNo;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="useTime", type="datetime",nullable=true)
     */
    private $useTime;

    /**
     * @var string
     *
     * @ORM\Column(name="mileage", type="decimal",scale=2,nullable=true)
     */
    private $mileage;

    /**
     * @var string
     *
     * @ORM\Column(name="startMileage", type="decimal",scale=2,nullable=true)
     */
    private $startMileage;

    /**
     * @var string
     *
     * @ORM\Column(name="endMileage", type="decimal",scale=2,nullable=true)
     */
    private $endMileage;


    /**
     * @var array
     *
     * @ORM\Column(name="cancelReason", type="json_array",nullable=true)
     */
    private $cancelReason;

    /**
     * @var integer
     *
     * @ORM\Column(name="source", type="integer",nullable=true)
     */
    private $source;


    /**
     * @var float
     *
     * @ORM\Column(name="refundAmount", type="float",nullable=true)
     */
    private $refundAmount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="refundTime", type="datetime",nullable=true)
     */
    private $refundTime;


    /**
     * Set wechatTransactionId
     *
     * @param string $wechatTransactionId
     * @return RentalOrder
     */
    public function setWechatTransactionId($wechatTransactionId)
    {
        $this->wechatTransactionId = $wechatTransactionId;

        return $this;
    }

    /**
     * Get wechatTransactionId
     *
     * @return string 
     */
    public function getWechatTransactionId()
    {
        return $this->wechatTransactionId;
    }

    /**
     * Set alipayTradeNo
     *
     * @param string $alipayTradeNo
     * @return RentalOrder
     */
    public function setAlipayTradeNo($alipayTradeNo)
    {
        $this->alipayTradeNo = $alipayTradeNo;

        return $this;
    }

    /**
     * Get alipayTradeNo
     *
     * @return string 
     */
    public function getAlipayTradeNo()
    {
        return $this->alipayTradeNo;
    }

    /**
     * Set useTime
     *
     * @param \DateTime $useTime
     * @return RentalOrder
     */
    public function setUseTime($useTime)
    {
        $this->useTime = $useTime;

        return $this;
    }

    /**
     * Get useTime
     *
     * @return \DateTime 
     */
    public function getUseTime()
    {
        return $this->useTime;
    }

    /**
     * Set mileage
     *
     * @param string $mileage
     * @return RentalOrder
     */
    public function setMileage($mileage)
    {
        $this->mileage = $mileage;

        return $this;
    }

    /**
     * Get mileage
     *
     * @return string 
     */
    public function getMileage()
    {
        return $this->mileage;
    }

    /**
     * Set cancelReason
     *
     * @param array $cancelReason
     * @return RentalOrder
     */
    public function setCancelReason($cancelReason)
    {
        $this->cancelReason = $cancelReason;

        return $this;
    }

    /**
     * Get cancelReason
     *
     * @return array 
     */
    public function getCancelReason()
    {
        return $this->cancelReason;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return RentalOrder
     */
    public function setRentalCar(\Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar = null)
    {
        $this->rentalCar = $rentalCar;

        return $this;
    }

    /**
     * Get rentalCar
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalCar 
     */
    public function getRentalCar()
    {
        return $this->rentalCar;
    }

    /**
     * Set pickUpStation
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStation $pickUpStation
     * @return RentalOrder
     */
    public function setPickUpStation(\Auto\Bundle\ManagerBundle\Entity\RentalStation $pickUpStation = null)
    {
        $this->pickUpStation = $pickUpStation;

        return $this;
    }

    /**
     * Get pickUpStation
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalStation 
     */
    public function getPickUpStation()
    {
        return $this->pickUpStation;
    }

    /**
     * Set returnStation
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStation $returnStation
     * @return RentalOrder
     */
    public function setReturnStation(\Auto\Bundle\ManagerBundle\Entity\RentalStation $returnStation = null)
    {
        $this->returnStation = $returnStation;

        return $this;
    }

    /**
     * Get returnStation
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalStation 
     */
    public function getReturnStation()
    {
        return $this->returnStation;
    }

    /**
     * Set coupon
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Coupon $coupon
     * @return RentalOrder
     */
    public function setCoupon(\Auto\Bundle\ManagerBundle\Entity\Coupon $coupon = null)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * Get coupon
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Coupon 
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * Set startMileage
     *
     * @param string $startMileage
     * @return RentalOrder
     */
    public function setStartMileage($startMileage)
    {
        $this->startMileage = $startMileage;

        return $this;
    }

    /**
     * Get startMileage
     *
     * @return string 
     */
    public function getStartMileage()
    {
        return $this->startMileage;
    }

    /**
     * Set endMileage
     *
     * @param string $endMileage
     * @return RentalOrder
     */
    public function setEndMileage($endMileage)
    {
        $this->endMileage = $endMileage;

        return $this;
    }

    /**
     * Get endMileage
     *
     * @return string 
     */
    public function getEndMileage()
    {
        return $this->endMileage;
    }

    /**
     * Set source
     *
     * @param integer $source
     * @return RentalOrder
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return integer 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set refundAmount
     *
     * @param float $refundAmount
     * @return RentalOrder
     */
    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;

        return $this;
    }

    /**
     * Get refundAmount
     *
     * @return float 
     */
    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    /**
     * Set refundTime
     *
     * @param \DateTime $refundTime
     * @return RentalOrder
     */
    public function setRefundTime($refundTime)
    {
        $this->refundTime = $refundTime;

        return $this;
    }

    /**
     * Get refundTime
     *
     * @return \DateTime 
     */
    public function getRefundTime()
    {
        return $this->refundTime;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     * @return RentalOrder
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string 
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set partner
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Partner $partner
     * @return RentalOrder
     */
    public function setPartner(\Auto\Bundle\ManagerBundle\Entity\Partner $partner = null)
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * Get partner
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Partner 
     */
    public function getPartner()
    {
        return $this->partner;
    }
}
