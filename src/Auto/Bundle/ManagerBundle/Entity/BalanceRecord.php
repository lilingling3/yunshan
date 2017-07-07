<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BalanceRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class BalanceRecord
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
     * @var integer
     *
     * @ORM\Column(name="memberId", type="integer")
     */
    private $memberId;

    /**
     * @var float
     *
     * @ORM\Column(name="actualAmount", type="float")
     */
    private $actualAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="consumptionAmount", type="float")
     */
    private $consumptionAmount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rechargeStartTime", type="datetime")
     */
    private $rechargeStartTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="refundTime", type="datetime", nullable=true)
     */
    private $refundTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;


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
     * Set memberId
     *
     * @param integer $memberId
     * @return BalanceRecord
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;

        return $this;
    }

    /**
     * Get memberId
     *
     * @return integer
     */
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * Set actualAmount
     *
     * @param float $actualAmount
     * @return BalanceRecord
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
     * Set amount
     *
     * @param float $amount
     * @return BalanceRecord
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
     * Set consumptionAmount
     *
     * @param float $consumptionAmount
     * @return BalanceRecord
     */
    public function setConsumptionAmount($consumptionAmount)
    {
        $this->consumptionAmount = $consumptionAmount;

        return $this;
    }

    /**
     * Get consumptionAmount
     *
     * @return float
     */
    public function getConsumptionAmount()
    {
        return $this->consumptionAmount;
    }

    /**
     * Set rechargeStartTime
     *
     * @param \DateTime $rechargeStartTime
     * @return BalanceRecord
     */
    public function setRechargeStartTime($rechargeStartTime)
    {
        $this->rechargeStartTime = $rechargeStartTime;

        return $this;
    }

    /**
     * Get rechargeStartTime
     *
     * @return \DateTime
     */
    public function getRechargeStartTime()
    {
        return $this->rechargeStartTime;
    }

    /**
     * Set refundTime
     *
     * @param \DateTime $refundTime
     * @return BalanceRecord
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
     * Set status
     *
     * @param integer $status
     * @return BalanceRecord
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}
