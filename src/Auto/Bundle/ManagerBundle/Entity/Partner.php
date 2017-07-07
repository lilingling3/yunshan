<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Partner
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Partner
{
    public function __construct()
    {
        $this->createTime = new \DateTime();
        $this->secret = md5(uniqid(null, true));
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="secret", type="string", length=255)
     */
    private $secret;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20,unique=true))
     */
    private $code;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float",nullable=true)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="actureAmount", type="float",nullable=true)
     */
    private $actureAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="float")
     */
    private $discount;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;


    /**
     * @var Member
     *
     * @ORM\OneToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_ids", type="text", nullable=true))
     */
    private $operatorIds;

    /**
     * @var integer
     *
     * @ORM\Column(name="operate_limit", type="integer", nullable=true)
     */
    private $operateLimit;

    /**
     * @var string
     *
     * @ORM\Column(name="visible_cars", type="text", nullable=true)
     */
    private $visibleCars;

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
     * Set name
     *
     * @param string $name
     * @return Partner
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set secret
     *
     * @param string $secret
     * @return Partner
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get secret
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Partner
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return Partner
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
     * Set amount
     *
     * @param float $amount
     * @return Partner
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
     * Set actureAmount
     *
     * @param float $actureAmount
     * @return Partner
     */
    public function setActureAmount($actureAmount)
    {
        $this->actureAmount = $actureAmount;

        return $this;
    }

    /**
     * Get actureAmount
     *
     * @return float
     */
    public function getActureAmount()
    {
        return $this->actureAmount;
    }

    /**
     * Set discount
     *
     * @param float $discount
     * @return Partner
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Partner
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

    /**
     * Set operatorIds
     *
     * @param string $operatorIds
     * @return Partner
     */
    public function setOperatorIds($operatorIds)
    {
        $this->operatorIds = $operatorIds;

        return $this;
    }

    /**
     * Get operatorIds
     *
     * @return string
     */
    public function getOperatorIds()
    {
        return $this->operatorIds;
    }

    /**
     * Set operateLimit
     *
     * @param integer $operateLimit
     * @return Partner
     */
    public function setOperateLimit($operateLimit)
    {
        $this->operateLimit = $operateLimit;

        return $this;
    }

    /**
     * Get operateLimit
     *
     * @return integer
     */
    public function getOperateLimit()
    {
        return $this->operateLimit;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return Partner
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
     * Set visibleCars
     *
     * @param string $visibleCars
     * @return Partner
     */
    public function setVisibleCars($visibleCars)
    {
        $this->visibleCars = $visibleCars;

        return $this;
    }

    /**
     * Get visibleCars
     *
     * @return string
     */
    public function getVisibleCars()
    {
        return $this->visibleCars;
    }
}

