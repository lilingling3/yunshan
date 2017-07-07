<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChargingRecords
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ChargingRecords
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
     * @var float
     *
     * @ORM\Column(name="degree", type="float")
     */
    private $degree;

    /**
     * @var float
     *
     * @ORM\Column(name="cost", type="float")
     */
    private $cost;

    /**
     * @var mileage
     *
     * @ORM\ManyToOne(targetEntity="MileageRecords")
     */
    private $mileage;

    /**
     * @var rentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var operator
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $operator;


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
     * Set degree
     *
     * @param float $degree
     * @return ChargingRecords
     */
    public function setDegree($degree)
    {
        $this->degree = $degree;

        return $this;
    }

    /**
     * Get degree
     *
     * @return float 
     */
    public function getDegree()
    {
        return $this->degree;
    }

    /**
     * Set cost
     *
     * @param float $cost
     * @return ChargingRecords
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return float 
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return ChargingRecords
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
     * Set mileage
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\MileageRecords $mileage
     * @return ChargingRecords
     */
    public function setMileage(\Auto\Bundle\ManagerBundle\Entity\MileageRecords $mileage = null)
    {
        $this->mileage = $mileage;

        return $this;
    }

    /**
     * Get mileage
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\MileageRecords 
     */
    public function getMileage()
    {
        return $this->mileage;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return ChargingRecords
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
     * Set operator
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $operator
     * @return ChargingRecords
     */
    public function setOperator(\Auto\Bundle\ManagerBundle\Entity\Member $operator = null)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getOperator()
    {
        return $this->operator;
    }
}
