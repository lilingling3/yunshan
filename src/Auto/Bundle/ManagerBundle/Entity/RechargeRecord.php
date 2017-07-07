<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RechargeRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RechargeRecord
{
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
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var RechargeOperate
     *
     * @ORM\ManyToOne(targetEntity="RechargeOperate")
     */
    private $operate;

    /**
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $operater;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float",nullable=true)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="walletAmount", type="float",nullable=true)
     */
    private $walletAmount;

    /**
     * @var RechargeOrder
     *
     * @ORM\ManyToOne(targetEntity="RechargeOrder")
     */
    private $rechargeOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255)
     */
    private $remark;

    public function __construct()
    {
        $this->createTime = new \DateTime();
    }

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
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return RechargeRecord
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return RechargeRecord
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
     * Set remark
     *
     * @param string $remark
     * @return RechargeRecord
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark
     *
     * @return string 
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set operate
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RechargeOperate $operate
     * @return RechargeRecord
     */
    public function setOperate(\Auto\Bundle\ManagerBundle\Entity\RechargeOperate $operate = null)
    {
        $this->operate = $operate;

        return $this;
    }

    /**
     * Get operate
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RechargeOperate
     */
    public function getOperate()
    {
        return $this->operate;
    }

    /**
     * Set amount
     *
     * @param float $amount
     * @return RechargeRecord
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get walletAmount
     *
     * @return float 
     */
    public function getWalletAmount()
    {
        return $this->walletAmount;
    }

    /**
     * Set walletAmount
     *
     * @param float $walletAmount
     * @return RechargeRecord
     */
    public function setWalletAmount($walletAmount)
    {
        $this->walletAmount = $walletAmount;

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
     * Set operater
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $operater
     * @return RechargeRecord
     */
    public function setOperater(\Auto\Bundle\ManagerBundle\Entity\Member $operater = null)
    {
        $this->operater = $operater;

        return $this;
    }

    /**
     * Get operater
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getOperater()
    {
        return $this->operater;
    }

    /**
     * Set rechargeOrder
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RechargeOrder $orderId
     * @return RechargeOrder
     */
    public function setRechargeOrder(\Auto\Bundle\ManagerBundle\Entity\RechargeOrder $orderId = null)
    {
        $this->rechargeOrder = $orderId;

        return $this;
    }

    /**
     * Get rechargeOrder
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RechargeOrder 
     */
    public function getRechargeOrder()
    {
        return $this->rechargeOrder;
    }
}
