<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefundRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RefundRecord
{
    const REFUND_STATUS_FOR_CHECK = 900;  //待审核
    const REFUND_STATUS_CHECK_OK = 901; //审核成功
    const REFUND_STATUS_CHECK_FAILED = 902; //审核失败
    const REFUND_STATUS_REFUND_OK = 903; //退款成功
    const REFUND_STATUS_REFUNDING = 904; //系统退款中

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
    private $createTime; //申请时间

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="checkTime", type="datetime",nullable=true)
     */
    private $checkTime;//审核时间

    /**
     * @var string
     *
     * @ORM\Column(name="refundInstrustions", type="string", length=255, nullable=true)
     */
    private $refundInstrustions;//退款说明

    /**
     * @var string
     *
     * @ORM\Column(name="checkFailedReason", type="string", length=255, nullable=true)
     */
    private $checkFailedReason; //审核失败原因

    /**
     * @var RechargeOrder
     *
     * @ORM\ManyToMany(targetEntity="RechargeOrder")
     * @ORM\OrderBy({"id"="ASC"})
     */
    private $rechargeOrders;//关联退款记录

    public function __construct()
    {
        $this->createTime = new \DateTime();
        $this->rechargeOrders = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return RefundRecord
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
     * Set checkTime
     *
     * @param \DateTime $checkTime
     * @return RefundRecord
     */
    public function setCheckTime($checkTime)
    {
        $this->checkTime = $checkTime;

        return $this;
    }

    /**
     * Get checkTime
     *
     * @return \DateTime 
     */
    public function getCheckTime()
    {
        return $this->checkTime;
    }

    /**
     * Set refundInstrustions
     *
     * @param string $refundInstrustions
     * @return RefundRecord
     */
    public function setRefundInstrustions($refundInstrustions)
    {
        $this->refundInstrustions = $refundInstrustions;

        return $this;
    }

    /**
     * Get refundInstrustions
     *
     * @return string 
     */
    public function getRefundInstrustions()
    {
        return $this->refundInstrustions;
    }

    /**
     * Set checkFailedReason
     *
     * @param string $checkFailedReason
     * @return RefundRecord
     */
    public function setCheckFailedReason($checkFailedReason)
    {
        $this->checkFailedReason = $checkFailedReason;

        return $this;
    }

    /**
     * Get checkFailedReason
     *
     * @return string 
     */
    public function getCheckFailedReason()
    {
        return $this->checkFailedReason;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return RefundRecord
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
     * Add rechargeOrders
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RechargeOrder $rechargeOrders
     * @return RefundRecord
     */
    public function addRechargeOrder(\Auto\Bundle\ManagerBundle\Entity\RechargeOrder $rechargeOrders)
    {
        $this->rechargeOrders[] = $rechargeOrders;

        return $this;
    }

    /**
     * Remove rechargeOrders
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RechargeOrder $rechargeOrders
     */
    public function removeRechargeOrder(\Auto\Bundle\ManagerBundle\Entity\RechargeOrder $rechargeOrders)
    {
        $this->rechargeOrders->removeElement($rechargeOrders);
    }

    /**
     * Get rechargeOrders
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRechargeOrders()
    {
        return $this->rechargeOrders;
    }
}
