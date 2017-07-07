<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InsuranceRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class InsuranceRecord
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
     * @ORM\Column(name="insuranceAmount", type="float")
     */
    private $insuranceAmount;

    /**
     * @var integer
     *
     * @ORM\Column(name="insurance", type="smallint")
     */
    private $insurance;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="insuranceTime", type="datetime")
     */
    private $insuranceTime;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="startTime", type="datetime",nullable=true)
     */
    private $startTime;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="endTime", type="datetime",nullable=true)
     */
    private $endTime;

    /**
     * @var RentalCar
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
     * @var company
     *
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $company;

   
    /**
     * @var string
     *
     * @ORM\Column(name="insuranceNumber", type="string", length=255)
     */
    private $insuranceNumber;




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
     * Set insuranceAmount
     *
     * @param float $insuranceAmount
     * @return InsuranceRecord
     */
    public function setInsuranceAmount($insuranceAmount)
    {
        $this->insuranceAmount = $insuranceAmount;

        return $this;
    }

    /**
     * Get insuranceAmount
     *
     * @return float 
     */
    public function getInsuranceAmount()
    {
        return $this->insuranceAmount;
    }

    /**
     * Set insurance
     *
     * @param integer $insurance
     * @return InsuranceRecord
     */
    public function setInsurance($insurance)
    {
        $this->insurance = $insurance;

        return $this;
    }

    /**
     * Get insurance
     *
     * @return integer 
     */
    public function getInsurance()
    {
        return $this->insurance;
    }

    /**
     * Set insuranceTime
     *
     * @param \DateTime $insuranceTime
     * @return InsuranceRecord
     */
    public function setInsuranceTime($insuranceTime)
    {
        $this->insuranceTime = $insuranceTime;

        return $this;
    }

    /**
     * Get insuranceTime
     *
     * @return \DateTime 
     */
    public function getInsuranceTime()
    {
        return $this->insuranceTime;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return InsuranceRecord
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime 
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return InsuranceRecord
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return InsuranceRecord
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
     * Set insuranceNumber
     *
     * @param string $insuranceNumber
     * @return InsuranceRecord
     */
    public function setInsuranceNumber($insuranceNumber)
    {
        $this->insuranceNumber = $insuranceNumber;

        return $this;
    }

    /**
     * Get insuranceNumber
     *
     * @return string 
     */
    public function getInsuranceNumber()
    {
        return $this->insuranceNumber;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return InsuranceRecord
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
     * Set company
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Company $company
     * @return InsuranceRecord
     */
    public function setCompany(\Auto\Bundle\ManagerBundle\Entity\Company $company = null)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Company 
     */
    public function getCompany()
    {
        return $this->company;
    }
}
