<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RechargeOrder
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RechargeOrder
{
    const RECHARGE_ORDER_PAYED_STATUS      = 1599;
    const RECHARGE_ORDER_UNPAID_STATUS      = 1500;

    public function __construct()
    {
        $this->createTime = new \DateTime();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float",nullable=true)
     */
    private $amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="payTime", type="datetime",nullable=true)
     */
    private $payTime;

    /**
     * @var float
     *
     * @ORM\Column(name="actualAmount", type="float")
     */
    private $actualAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="refundAmount", type="float",nullable=true,options={"comment"="用户钱包扣除的金额"})
     */
    private $refundAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="actualRefundAmount", type="float",nullable=true,options={"comment"="实际退给用户的金额"})
     */
    private $actualRefundAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="wechatRefundId", type="string", length=255,nullable=true,options={"comment"="微信退款单号"})
     */
    private $wechatRefundId;

    /**
     * @var string
     *
     * @ORM\Column(name="alipayRefundNo", type="string", length=255,nullable=true,options={"comment"="阿里退款单号"})
     */
    private $alipayRefundNo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="refundTime", type="datetime",nullable=true)
     */
    private $refundTime;



    /**
     * @var string
     *
     * @ORM\Column(name="wechatTransactionId", type="string", length=255,nullable=true)
     */
    private $wechatTransactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="alipayTradeNo", type="string", length=255,nullable=true)
     */
    private $alipayTradeNo;

    /**
     * @var activity
     *
     * @ORM\ManyToOne(targetEntity="RechargeActivity")
     */
    private $activity;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set amount
     *
     * @param float $amount
     * @return RechargeOrder
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return RechargeOrder
     */
    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return \DateTime 
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * Set payTime
     *
     * @param \DateTime $payTime
     * @return RechargeOrder
     */
    public function setPayTime($payTime)
    {
        $this->payTime = $payTime;

        return $this;
    }

    /**
     * Get payTime
     *
     * @return \DateTime 
     */
    public function getPayTime()
    {
        return $this->payTime;
    }

    /**
     * Set actualAmount
     *
     * @param float $actualAmount
     * @return RechargeOrder
     */
    public function setActualAmount($actualAmount)
    {
        $this->actualAmount = $actualAmount;

        return $this;
    }

    /**
     * Get actualAmount
     *
     * @return float 
     */
    public function getActualAmount()
    {
        return $this->actualAmount;
    }

    /**
     * Set wechatTransactionId
     *
     * @param string $wechatTransactionId
     * @return RechargeOrder
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
     * @return RechargeOrder
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
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return RechargeOrder
     */
    public function setMember(\Auto\Bundle\ManagerBundle\Entity\Member $member = null)
    {
        $this->member = $member;

        return $this;
    }

    /**
     * Get member
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set activity
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RechargeActivity $activity
     * @return RechargeOrder
     */
    public function setActivity(\Auto\Bundle\ManagerBundle\Entity\RechargeActivity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RechargeActivity 
     */
    public function getActivity()
    {
        return $this->activity;
    }

    public function getStatus(){
        if($this->getPayTime()){

            return self::RECHARGE_ORDER_PAYED_STATUS;
        }else{

            return self::RECHARGE_ORDER_UNPAID_STATUS;

        }


    }


    /**
     * Set refundAmount
     *
     * @param float $refundAmount
     * @return RechargeOrder
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
     * @return RechargeOrder
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
     * Set actualRefundAmount
     *
     * @param float $actualRefundAmount
     * @return RechargeOrder
     */
    public function setActualRefundAmount($actualRefundAmount)
    {
        $this->actualRefundAmount = $actualRefundAmount;

        return $this;
    }

    /**
     * Get actualRefundAmount
     *
     * @return float 
     */
    public function getActualRefundAmount()
    {
        return $this->actualRefundAmount;
    }

    /**
     * Set wechatRefundId
     *
     * @param string $wechatRefundId
     * @return RechargeOrder
     */
    public function setWechatRefundId($wechatRefundId)
    {
        $this->wechatRefundId = $wechatRefundId;

        return $this;
    }

    /**
     * Get wechatRefundId
     *
     * @return string 
     */
    public function getWechatRefundId()
    {
        return $this->wechatRefundId;
    }

    /**
     * Set alipayRefundNo
     *
     * @param string $alipayRefundNo
     * @return RechargeOrder
     */
    public function setAlipayRefundNo($alipayRefundNo)
    {
        $this->alipayRefundNo = $alipayRefundNo;

        return $this;
    }

    /**
     * Get alipayRefundNo
     *
     * @return string 
     */
    public function getAlipayRefundNo()
    {
        return $this->alipayRefundNo;
    }
}
