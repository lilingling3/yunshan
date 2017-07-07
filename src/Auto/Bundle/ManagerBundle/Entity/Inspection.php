<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Inspection
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Inspection
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
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="inspectionTime", type="datetime")
     */
    private $inspectionTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="nextInspectionTime", type="datetime",nullable=true)
     */
    private $nextInspectionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255,nullable=true)
     */
    private $remark;

    /**
     * @var string
     *
     * @ORM\Column(name="inspectionYear", type="string", length=10,nullable=true)
     */
    private $inspectionYear;

    /**
     * @var RentalCar
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return Inspection
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
     * Set inspectionTime
     *
     * @param \DateTime $inspectionTime
     * @return Inspection
     */
    public function setInspectionTime($inspectionTime)
    {
        $this->inspectionTime = $inspectionTime;

        return $this;
    }

    /**
     * Get inspectionTime
     *
     * @return \DateTime 
     */
    public function getInspectionTime()
    {
        return $this->inspectionTime;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return Inspection
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
     * Set nextInspectionTime
     *
     * @param \DateTime $nextInspectionTime
     * @return Inspection
     */
    public function setNextInspectionTime($nextInspectionTime)
    {
        $this->nextInspectionTime = $nextInspectionTime;

        return $this;
    }

    /**
     * Get nextInspectionTime
     *
     * @return \DateTime 
     */
    public function getNextInspectionTime()
    {
        return $this->nextInspectionTime;
    }

    /**
     * Set remark
     *
     * @param string $remark
     * @return Inspection
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark
     *
     * @return string 
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set inspectionYear
     *
     * @param string $inspectionYear
     * @return Inspection
     */
    public function setInspectionYear($inspectionYear)
    {
        $this->inspectionYear = $inspectionYear;

        return $this;
    }

    /**
     * Get inspectionYear
     *
     * @return string 
     */
    public function getInspectionYear()
    {
        return $this->inspectionYear;
    }
}
