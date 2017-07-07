<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Invoice
{
    const AUTH_STATUS_SUCCESS = 1101; //已审核
    const AUTH_STATUS_FAIL = 1100; //为审核
    const DELIVERY_STATUS_SEND = 1201; //已发送
    const DELIVERY_STATUS_UNSENT = 1200; //未发送


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
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="deliveryName", type="string", length=255)
     */
    private $deliveryName;

    /**
     * @var string
     *
     * @ORM\Column(name="deliveryAddress", type="string", length=255)
     */
    private $deliveryAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="deliveryMobile", type="string", length=255)
     */
    private $deliveryMobile;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="authTime", type="datetime",nullable=true)
     */
    private $authTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deliveryTime", type="datetime",nullable=true)
     */
    private $deliveryTime;

    /**
     * @var deliveryCompany
     *
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $deliveryCompany;

    /**
     * @var string
     *
     * @ORM\Column(name="deliveryNumber", type="string", length=255,nullable=true)
     */
    private $deliveryNumber;

    /**
     * @var deliveryMember
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */

    private $deliveryMember;

    /**
     * @var authMember
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */

    private $authMember;

    /**
     * @var applyMember
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $applyMember;



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
     * @return Invoice
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
     * Set title
     *
     * @param string $title
     * @return Invoice
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set deliveryName
     *
     * @param string $deliveryName
     * @return Invoice
     */
    public function setDeliveryName($deliveryName)
    {
        $this->deliveryName = $deliveryName;

        return $this;
    }

    /**
     * Get deliveryName
     *
     * @return string 
     */
    public function getDeliveryName()
    {
        return $this->deliveryName;
    }

    /**
     * Set deliveryAddress
     *
     * @param string $deliveryAddress
     * @return Invoice
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    /**
     * Get deliveryAddress
     *
     * @return string 
     */
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    /**
     * Set deliveryMobile
     *
     * @param string $deliveryMobile
     * @return Invoice
     */
    public function setDeliveryMobile($deliveryMobile)
    {
        $this->deliveryMobile = $deliveryMobile;

        return $this;
    }

    /**
     * Get deliveryMobile
     *
     * @return string 
     */
    public function getDeliveryMobile()
    {
        return $this->deliveryMobile;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return Invoice
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
     * Set authTime
     *
     * @param \DateTime $authTime
     * @return Invoice
     */
    public function setAuthTime($authTime)
    {
        $this->authTime = $authTime;

        return $this;
    }

    /**
     * Get authTime
     *
     * @return \DateTime 
     */
    public function getAuthTime()
    {
        return $this->authTime;
    }

    /**
     * Set deliveryTime
     *
     * @param \DateTime $deliveryTime
     * @return Invoice
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return \DateTime 
     */
    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    /**
     * Set deliveryNumber
     *
     * @param string $deliveryNumber
     * @return Invoice
     */
    public function setDeliveryNumber($deliveryNumber)
    {
        $this->deliveryNumber = $deliveryNumber;

        return $this;
    }

    /**
     * Get deliveryNumber
     *
     * @return string 
     */
    public function getDeliveryNumber()
    {
        return $this->deliveryNumber;
    }

    /**
     * Set deliveryCompany
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Company $deliveryCompany
     * @return Invoice
     */
    public function setDeliveryCompany(\Auto\Bundle\ManagerBundle\Entity\Company $deliveryCompany = null)
    {
        $this->deliveryCompany = $deliveryCompany;

        return $this;
    }

    /**
     * Get deliveryCompany
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Company 
     */
    public function getDeliveryCompany()
    {
        return $this->deliveryCompany;
    }

    /**
     * Set deliveryPerson
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $deliveryPerson
     * @return Invoice
     */
    public function setDeliveryPerson(\Auto\Bundle\ManagerBundle\Entity\Member $deliveryPerson = null)
    {
        $this->deliveryPerson = $deliveryPerson;

        return $this;
    }

    /**
     * Get deliveryPerson
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getDeliveryPerson()
    {
        return $this->deliveryPerson;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return Invoice
     */
    public function setMember(\Auto\Bundle\ManagerBundle\Entity\Member $member = null)
    {
        $this->member = $member;

        return $this;
    }

    /**
     * Get member
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\apply
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set authMember
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $authMember
     * @return Invoice
     */
    public function setAuthMember(\Auto\Bundle\ManagerBundle\Entity\Member $authMember = null)
    {
        $this->authMember = $authMember;

        return $this;
    }

    /**
     * Get authMember
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getAuthMember()
    {
        return $this->authMember;
    }

    /**
     * Set applyMember
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $applyMember
     * @return Invoice
     */
    public function setApplyMember(\Auto\Bundle\ManagerBundle\Entity\Member $applyMember = null)
    {
        $this->applyMember = $applyMember;

        return $this;
    }

    /**
     * Get applyMember
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member
     */
    public function getApplyMember()
    {
        return $this->applyMember;
    }

    /**
     * Set deliveryMember
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $deliveryMember
     * @return Invoice
     */
    public function setDeliveryMember(\Auto\Bundle\ManagerBundle\Entity\Member $deliveryMember = null)
    {
        $this->deliveryMember = $deliveryMember;

        return $this;
    }

    /**
     * Get deliveryMember
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getDeliveryMember()
    {
        return $this->deliveryMember;
    }
}
