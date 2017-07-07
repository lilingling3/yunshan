<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatisticsAmountRecord
 * 各种总数统计表
 * @ORM\Table()
 * @ORM\Entity
 */
class StatisticsAmountRecord
{
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
     * @var \DateTime
     *
     * @ORM\Column(name="dateTime", type="date")
     */
    private $dateTime;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;


    /**
     * @var float
     *
     * @ORM\Column(name="stayTime", type="float",nullable=true)
     */


    /**
     * @var integer
     * 注册用户总数
     * @ORM\Column(name="registMembers", type="integer",nullable=true)
     */
    private $registMembers;

    /**
     * @var integer
     * 提交认证用户总数
     * @ORM\Column(name="authMembers", type="integer",nullable=true)
     */
    private $authMembers;

    /**
     * @var integer
     * 认证通过用户总数
     * @ORM\Column(name="verifiedMembers", type="integer",nullable=true)
     */
    private $verifiedMembers;

    /**
     * @var integer
     * 充值用户总数
     * @ORM\Column(name="rechargeMembers", type="integer",nullable=true)
     */
    private $rechargeMembers;

    /**
     * @var float
     * 实际充值总金额
     * @ORM\Column(name="actualRecharges", type="float",nullable=true)
     */
    private $actualRecharges;

    /**
     * @var float
     * 充值后对应总金额
     * @ORM\Column(name="recharges", type="float",nullable=true)
     */
    private $recharges;

    /**
     * @var integer
     * 订单总数
     * @ORM\Column(name="orders", type="integer",nullable=true)
     */
    private $orders;

    /**
     * @var integer
     * 已取消订单总数
     * @ORM\Column(name="cancelOrders", type="integer",nullable=true)
     */
    private $cancelOrders;

    /**
     * @var float
     * 总完成订单应收费用（元）
     * @ORM\Column(name="dueAmount", type="float",nullable=true)
     */
    private $dueAmount;

    /**
     * @var float
     * 总完成订单减免费用（元）
     * @ORM\Column(name="reliefAmount", type="float",nullable=true)
     */
    private $reliefAmount;

    /**
     * @var float
     * 总完成订单优惠券抵用金额（元）
     * @ORM\Column(name="couponAmount", type="float",nullable=true)
     */
    private $couponAmount;

    /**
     * @var float
     * 总完成订单实收费用（元）
     * @ORM\Column(name="actualAmount", type="float",nullable=true)
     */
    private $actualAmount;




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
     * Set dateTime
     *
     * @param \DateTime $dateTime
     * @return StatisticsAmountRecord
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * Get dateTime
     *
     * @return \DateTime 
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return StatisticsAmountRecord
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
     * Set registMembers
     *
     * @param integer $registMembers
     * @return StatisticsAmountRecord
     */
    public function setRegistMembers($registMembers)
    {
        $this->registMembers = $registMembers;

        return $this;
    }

    /**
     * Get registMembers
     *
     * @return integer 
     */
    public function getRegistMembers()
    {
        return $this->registMembers;
    }

    /**
     * Set authMembers
     *
     * @param integer $authMembers
     * @return StatisticsAmountRecord
     */
    public function setAuthMembers($authMembers)
    {
        $this->authMembers = $authMembers;

        return $this;
    }

    /**
     * Get authMembers
     *
     * @return integer 
     */
    public function getAuthMembers()
    {
        return $this->authMembers;
    }

    /**
     * Set verifiedMembers
     *
     * @param integer $verifiedMembers
     * @return StatisticsAmountRecord
     */
    public function setVerifiedMembers($verifiedMembers)
    {
        $this->verifiedMembers = $verifiedMembers;

        return $this;
    }

    /**
     * Get verifiedMembers
     *
     * @return integer 
     */
    public function getVerifiedMembers()
    {
        return $this->verifiedMembers;
    }

    /**
     * Set rechargeMembers
     *
     * @param integer $rechargeMembers
     * @return StatisticsAmountRecord
     */
    public function setRechargeMembers($rechargeMembers)
    {
        $this->rechargeMembers = $rechargeMembers;

        return $this;
    }

    /**
     * Get rechargeMembers
     *
     * @return integer 
     */
    public function getRechargeMembers()
    {
        return $this->rechargeMembers;
    }

    /**
     * Set actualRecharges
     *
     * @param float $actualRecharges
     * @return StatisticsAmountRecord
     */
    public function setActualRecharges($actualRecharges)
    {
        $this->actualRecharges = $actualRecharges;

        return $this;
    }

    /**
     * Get actualRecharges
     *
     * @return float 
     */
    public function getActualRecharges()
    {
        return $this->actualRecharges;
    }

    /**
     * Set recharges
     *
     * @param float $recharges
     * @return StatisticsAmountRecord
     */
    public function setRecharges($recharges)
    {
        $this->recharges = $recharges;

        return $this;
    }

    /**
     * Get recharges
     *
     * @return float 
     */
    public function getRecharges()
    {
        return $this->recharges;
    }

    /**
     * Set orders
     *
     * @param integer $orders
     * @return StatisticsAmountRecord
     */
    public function setOrders($orders)
    {
        $this->orders = $orders;

        return $this;
    }

    /**
     * Get orders
     *
     * @return integer 
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Set cancelOrders
     *
     * @param integer $cancelOrders
     * @return StatisticsAmountRecord
     */
    public function setCancelOrders($cancelOrders)
    {
        $this->cancelOrders = $cancelOrders;

        return $this;
    }

    /**
     * Get cancelOrders
     *
     * @return integer 
     */
    public function getCancelOrders()
    {
        return $this->cancelOrders;
    }

    /**
     * Set dueAmount
     *
     * @param float $dueAmount
     * @return StatisticsAmountRecord
     */
    public function setDueAmount($dueAmount)
    {
        $this->dueAmount = $dueAmount;

        return $this;
    }

    /**
     * Get dueAmount
     *
     * @return float 
     */
    public function getDueAmount()
    {
        return $this->dueAmount;
    }

    /**
     * Set reliefAmount
     *
     * @param float $reliefAmount
     * @return StatisticsAmountRecord
     */
    public function setReliefAmount($reliefAmount)
    {
        $this->reliefAmount = $reliefAmount;

        return $this;
    }

    /**
     * Get reliefAmount
     *
     * @return float 
     */
    public function getReliefAmount()
    {
        return $this->reliefAmount;
    }

    /**
     * Set couponAmount
     *
     * @param float $couponAmount
     * @return StatisticsAmountRecord
     */
    public function setCouponAmount($couponAmount)
    {
        $this->couponAmount = $couponAmount;

        return $this;
    }

    /**
     * Get couponAmount
     *
     * @return float 
     */
    public function getCouponAmount()
    {
        return $this->couponAmount;
    }

    /**
     * Set actualAmount
     *
     * @param float $actualAmount
     * @return StatisticsAmountRecord
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
}
