<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentOrder
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class PaymentOrder
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
     * @ORM\Column(name="amount", type="float")
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
     * @ORM\Column(name="payTime", type="datetime", nullable=true)
     */
    private $payTime;

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
     * @var integer
     *
     * @ORM\Column(name="kind", type="integer")
     */
    private $kind;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=255,nullable=true)
     */
    private $reason;



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
     * @return PaymentOrder
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
     * @return PaymentOrder
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
     * @return PaymentOrder
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
     * Set wechatTransactionId
     *
     * @param string $wechatTransactionId
     * @return PaymentOrder
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
     * @return PaymentOrder
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
     * Set kind
     *
     * @param integer $kind
     * @return PaymentOrder
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind
     *
     * @return integer 
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return PaymentOrder
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return PaymentOrder
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
}
