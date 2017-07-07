<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CouponKind
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class CouponKind
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="validDay", type="integer")
     */
    private $validDay;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;


    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="needHour", type="integer")
     */
    private $needHour;

    /**
     * @var float
     *
     * @ORM\Column(name="needAmount", type="float")
     */
    private $needAmount;

    /**
     * @var CarLevel
     *
     * @ORM\ManyToOne(targetEntity="CarLevel")
     */
    private $carLevel;

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
     * @return CouponKind
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
     * Set validDay
     *
     * @param integer $validDay
     * @return CouponKind
     */
    public function setValidDay($validDay)
    {
        $this->validDay = $validDay;

        return $this;
    }

    /**
     * Get validDay
     *
     * @return integer 
     */
    public function getValidDay()
    {
        return $this->validDay;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return CouponKind
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
     * @return CouponKind
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
     * Set needHour
     *
     * @param integer $needHour
     * @return CouponKind
     */
    public function setNeedHour($needHour)
    {
        $this->needHour = $needHour;

        return $this;
    }

    /**
     * Get needHour
     *
     * @return integer 
     */
    public function getNeedHour()
    {
        return $this->needHour;
    }

    /**
     * Set needAmount
     *
     * @param float $needAmount
     * @return CouponKind
     */
    public function setNeedAmount($needAmount)
    {
        $this->needAmount = $needAmount;

        return $this;
    }

    /**
     * Get needAmount
     *
     * @return float 
     */
    public function getNeedAmount()
    {
        return $this->needAmount;
    }

    /**
     * Set carLevel
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\CarLevel $carLevel
     * @return CouponKind
     */
    public function setCarLevel(\Auto\Bundle\ManagerBundle\Entity\CarLevel $carLevel = null)
    {
        $this->carLevel = $carLevel;

        return $this;
    }

    /**
     * Get carLevel
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\CarLevel 
     */
    public function getCarLevel()
    {
        return $this->carLevel;
    }
}
