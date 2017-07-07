<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Coupon
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Coupon
{
    const COUPON_USABLE = 400; //可用
    const COUPON_USED = 401; //已使用
    const COUPON_EXPIRE = 402; //已过期

    const ORDER_COUPON_USABLE = 501;//可用
    const ORDER_COUPON_UNUSABLE = 500;//不可用

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
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=11,nullable=true)
     */
    private $mobile;

    /**
     * @var CouponKind
     *
     * @ORM\ManyToOne(targetEntity="CouponKind")
     */
    private $kind;

    /**
     * @var activity
     *
     * @ORM\ManyToOne(targetEntity="CouponActivity")
     */
    private $activity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endTime", type="datetime")
     */
    private $endTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="useTime", type="datetime",nullable=true)
     */
    private $useTime;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255,nullable=true)
     */
    private $code;

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
     * @return Coupon
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
     * @return Coupon
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
     * @return Coupon
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
     * Set kind
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\CouponKind $kind
     * @return Coupon
     */
    public function setKind(\Auto\Bundle\ManagerBundle\Entity\CouponKind $kind = null)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\CouponKind 
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set useTime
     *
     * @param \DateTime $useTime
     * @return Coupon
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
     * Set activity
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\CouponActivity $activity
     * @return Coupon
     */
    public function setActivity(\Auto\Bundle\ManagerBundle\Entity\CouponActivity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\CouponActivity 
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Coupon
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
     * Set mobile
     *
     * @param string $mobile
     * @return Coupon
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
}
