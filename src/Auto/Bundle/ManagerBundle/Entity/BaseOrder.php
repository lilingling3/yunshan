<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BaseOrder
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 */
class BaseOrder
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
     * @var float
     *
     * @ORM\Column(name="dueAmount", type="float",nullable=true)
     */
    private $dueAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="walletAmount", type="float",nullable=true)
     */

    private $walletAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="reliefAmount", type="float",nullable=true)
     */

    private $reliefAmount;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */

    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endTime", type="datetime", nullable=true)
     */
    private $endTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="payTime", type="datetime", nullable=true)
     */
    private $payTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cancelTime", type="datetime", nullable=true)
     */
    private $cancelTime;


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
     * @return BaseOrder
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
     * @return BaseOrder
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
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return BaseOrder
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime 
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return BaseOrder
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
     * Set payTime
     *
     * @param \DateTime $payTime
     * @return BaseOrder
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
     * Set cancelTime
     *
     * @param \DateTime $cancelTime
     * @return BaseOrder
     */
    public function setCancelTime($cancelTime)
    {
        $this->cancelTime = $cancelTime;

        return $this;
    }

    /**
     * Get cancelTime
     *
     * @return \DateTime 
     */
    public function getCancelTime()
    {
        return $this->cancelTime;
    }

    /**
     * Set walletAmount
     *
     * @param float $walletAmount
     * @return BaseOrder
     */
    public function setWalletAmount($walletAmount)
    {
        $this->walletAmount = $walletAmount;

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
     * Set reliefAmount
     *
     * @param float $reliefAmount
     * @return BaseOrder
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
     * Set dueAmount
     *
     * @param float $dueAmount
     * @return BaseOrder
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
}
