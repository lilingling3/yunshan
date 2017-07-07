<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatisticsOperateRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class StatisticsOperateRecord
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
     * @var RentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var RentalStation
     *
     * @ORM\ManyToOne(targetEntity="RentalStation")
     */
    private $rentalStation;

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

    private $stayTime;

    /**
     * @var float
     *
     * @ORM\Column(name="rentalTime", type="float",nullable=true)
     */

    private $rentalTime;

    /**
     * @var float
     *
     * @ORM\Column(name="dayRentalTime", type="float",nullable=true)
     */

    private $dayRentalTime;

    /**
     * @var float
     *
     * @ORM\Column(name="nightRentalTime", type="float",nullable=true)
     */

    private $nightRentalTime;


    /**
     * @var integer
     *
     * @ORM\Column(name="orderCount", type="integer",nullable=true)
     */
    private $orderCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="dayOrderCount", type="integer",nullable=true)
     */
    private $dayOrderCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="nightOrderCount", type="integer",nullable=true)
     */
    private $nightOrderCount;

    /**
     * @var float
     *
     * @ORM\Column(name="revenueAmount", type="float",nullable=true)
     */
    private $revenueAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="couponAmount", type="float",nullable=true)
     */
    private $couponAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="rentalAmount", type="float",nullable=true)
     */
    private $rentalAmount;


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
     * @return StatisticsOperateRecord
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
     * @return StatisticsOperateRecord
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
     * Set stayTime
     *
     * @param float $stayTime
     * @return StatisticsOperateRecord
     */
    public function setStayTime($stayTime)
    {
        $this->stayTime = $stayTime;

        return $this;
    }

    /**
     * Get stayTime
     *
     * @return float 
     */
    public function getStayTime()
    {
        return $this->stayTime;
    }

    /**
     * Set rentalTime
     *
     * @param float $rentalTime
     * @return StatisticsOperateRecord
     */
    public function setRentalTime($rentalTime)
    {
        $this->rentalTime = $rentalTime;

        return $this;
    }

    /**
     * Get rentalTime
     *
     * @return float 
     */
    public function getRentalTime()
    {
        return $this->rentalTime;
    }

    /**
     * Set dayRentalTime
     *
     * @param float $dayRentalTime
     * @return StatisticsOperateRecord
     */
    public function setDayRentalTime($dayRentalTime)
    {
        $this->dayRentalTime = $dayRentalTime;

        return $this;
    }

    /**
     * Get dayRentalTime
     *
     * @return float 
     */
    public function getDayRentalTime()
    {
        return $this->dayRentalTime;
    }

    /**
     * Set nightRentalTime
     *
     * @param float $nightRentalTime
     * @return StatisticsOperateRecord
     */
    public function setNightRentalTime($nightRentalTime)
    {
        $this->nightRentalTime = $nightRentalTime;

        return $this;
    }

    /**
     * Get nightRentalTime
     *
     * @return float 
     */
    public function getNightRentalTime()
    {
        return $this->nightRentalTime;
    }

    /**
     * Set orderCount
     *
     * @param integer $orderCount
     * @return StatisticsOperateRecord
     */
    public function setOrderCount($orderCount)
    {
        $this->orderCount = $orderCount;

        return $this;
    }

    /**
     * Get orderCount
     *
     * @return integer 
     */
    public function getOrderCount()
    {
        return $this->orderCount;
    }

    /**
     * Set dayOrderCount
     *
     * @param integer $dayOrderCount
     * @return StatisticsOperateRecord
     */
    public function setDayOrderCount($dayOrderCount)
    {
        $this->dayOrderCount = $dayOrderCount;

        return $this;
    }

    /**
     * Get dayOrderCount
     *
     * @return integer 
     */
    public function getDayOrderCount()
    {
        return $this->dayOrderCount;
    }

    /**
     * Set nightOrderCount
     *
     * @param integer $nightOrderCount
     * @return StatisticsOperateRecord
     */
    public function setNightOrderCount($nightOrderCount)
    {
        $this->nightOrderCount = $nightOrderCount;

        return $this;
    }

    /**
     * Get nightOrderCount
     *
     * @return integer 
     */
    public function getNightOrderCount()
    {
        return $this->nightOrderCount;
    }

    /**
     * Set revenueAmount
     *
     * @param float $revenueAmount
     * @return StatisticsOperateRecord
     */
    public function setRevenueAmount($revenueAmount)
    {
        $this->revenueAmount = $revenueAmount;

        return $this;
    }

    /**
     * Get revenueAmount
     *
     * @return float 
     */
    public function getRevenueAmount()
    {
        return $this->revenueAmount;
    }

    /**
     * Set couponAmount
     *
     * @param float $couponAmount
     * @return StatisticsOperateRecord
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
     * Set rentalAmount
     *
     * @param float $rentalAmount
     * @return StatisticsOperateRecord
     */
    public function setRentalAmount($rentalAmount)
    {
        $this->rentalAmount = $rentalAmount;

        return $this;
    }

    /**
     * Get rentalAmount
     *
     * @return float 
     */
    public function getRentalAmount()
    {
        return $this->rentalAmount;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return StatisticsOperateRecord
     */
    public function setRentalCar(\Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar = null)
    {
        $this->rentalCar = $rentalCar;

        return $this;
    }

    /**
     * Get rentalCar
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalCar 
     */
    public function getRentalCar()
    {
        return $this->rentalCar;
    }

    /**
     * Set rentalStation
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation
     * @return StatisticsOperateRecord
     */
    public function setRentalStation(\Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation = null)
    {
        $this->rentalStation = $rentalStation;

        return $this;
    }

    /**
     * Get rentalStation
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalStation 
     */
    public function getRentalStation()
    {
        return $this->rentalStation;
    }
}
