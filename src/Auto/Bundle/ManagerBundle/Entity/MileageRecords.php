<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MileageRecords
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class MileageRecords
{

    const USE_CAR_KIND      = 1;
    const BACK_CAR_KIND     = 2;
    const CHARGING_CAR_KIND = 3;


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
     * @ORM\Column(name="mileage", type="float")
     */
    private $mileage;

    /**
     * @var integer
     *
     * @ORM\Column(name="kind", type="smallint")
     */
    private $kind;
    /**
     * @var rentalOrder
     *
     * @ORM\ManyToOne(targetEntity="RentalOrder")
     */
    private $rentalOrder;

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
     * @var rentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;


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
     * Set mileage
     *
     * @param float $mileage
     * @return MileageRecords
     */
    public function setMileage($mileage)
    {
        $this->mileage = $mileage;

        return $this;
    }

    /**
     * Get mileage
     *
     * @return float 
     */
    public function getMileage()
    {
        return $this->mileage;
    }

    /**
     * Set kind
     *
     * @param integer $kind
     * @return MileageRecords
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return MileageRecords
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
     * Set rentalOrder
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalOrder $rentalOrder
     * @return MileageRecords
     */
    public function setRentalOrder(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $rentalOrder = null)
    {
        $this->rentalOrder = $rentalOrder;

        return $this;
    }

    /**
     * Get rentalOrder
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalOrder 
     */
    public function getRentalOrder()
    {
        return $this->rentalOrder;
    }

    /**
     * Set operator
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $operator
     * @return MileageRecords
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

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return MileageRecords
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
}
