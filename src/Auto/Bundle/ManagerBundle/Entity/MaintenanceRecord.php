<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Maintain
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class MaintenanceRecord
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
     * @ORM\Column(name="createTime", type="datetime",nullable=true)
     */
    private $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="maintenanceReason", type="string", length=255)
     */
    private $maintenanceReason;

    /**
     * @var string
     *
     * @ORM\Column(name="thirdPartyLicensePlate", type="string", length=255,nullable=true)
     */
    private $thirdPartyLicensePlate;

    /**
     * @var RentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */

    private $rentalCar;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="maintenanceTime", type="datetime", nullable=true)
     */

    private $maintenanceTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="downTime", type="datetime",nullable=true)
     */

    private $downTime;

    /**
     * @var string
     *
     * @ORM\Column(name="maintenanceProject", type="text",nullable=true)
     */
    private $maintenanceProject;


    /**
     * @var integer
     *
     * @ORM\Column(name="kind", type="smallint")
     */
    private $kind;

    /**
     * @var float
     *
     * @ORM\Column(name="maintenanceAmount", type="float")
     */

    private $maintenanceAmount;

//    /**
//     * @var integer
//     *
//     * @ORM\Column(name="status", type="smallint",options={"default"=0})
//     */
//
//    private $status;

    /**
     * @var company
     *
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $company;

    /**
     * @var array
     *
     * @ORM\Column(name="images", type="json_array",nullable=true)
     */
    private $images;

    /**
     * @var MaintenanceRecord
     *
     * @ORM\ManyToOne(targetEntity="MaintenanceRecord")
     */

    private $parent;




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
     * Set maintenanceReason
     *
     * @param string $maintenanceReason
     * @return MaintenanceRecord
     */
    public function setMaintenanceReason($maintenanceReason)
    {
        $this->maintenanceReason = $maintenanceReason;

        return $this;
    }

    /**
     * Get maintenanceReason
     *
     * @return string 
     */
    public function getMaintenanceReason()
    {
        return $this->maintenanceReason;
    }

    /**
     * Set thirdPartyLicensePlate
     *
     * @param string $thirdPartyLicensePlate
     * @return MaintenanceRecord
     */
    public function setThirdPartyLicensePlate($thirdPartyLicensePlate)
    {
        $this->thirdPartyLicensePlate = $thirdPartyLicensePlate;

        return $this;
    }

    /**
     * Get thirdPartyLicensePlate
     *
     * @return string 
     */
    public function getThirdPartyLicensePlate()
    {
        return $this->thirdPartyLicensePlate;
    }

    /**
     * Set maintenanceTime
     *
     * @param \DateTime $maintenanceTime
     * @return MaintenanceRecord
     */
    public function setMaintenanceTime($maintenanceTime)
    {
        $this->maintenanceTime = $maintenanceTime;

        return $this;
    }

    /**
     * Get maintenanceTime
     *
     * @return \DateTime 
     */
    public function getMaintenanceTime()
    {
        return $this->maintenanceTime;
    }

    /**
     * Set downTime
     *
     * @param \DateTime $downTime
     * @return MaintenanceRecord
     */
    public function setDownTime($downTime)
    {
        $this->downTime = $downTime;

        return $this;
    }

    /**
     * Get downTime
     *
     * @return \DateTime 
     */
    public function getDownTime()
    {
        return $this->downTime;
    }

    /**
     * Set maintenanceProject
     *
     * @param string $maintenanceProject
     * @return MaintenanceRecord
     */
    public function setMaintenanceProject($maintenanceProject)
    {
        $this->maintenanceProject = $maintenanceProject;

        return $this;
    }

    /**
     * Get maintenanceProject
     *
     * @return string 
     */
    public function getMaintenanceProject()
    {
        return $this->maintenanceProject;
    }

    /**
     * Set kind
     *
     * @param integer $kind
     * @return MaintenanceRecord
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
     * Set maintenanceAmount
     *
     * @param float $maintenanceAmount
     * @return MaintenanceRecord
     */
    public function setMaintenanceAmount($maintenanceAmount)
    {
        $this->maintenanceAmount = $maintenanceAmount;

        return $this;
    }

    /**
     * Get maintenanceAmount
     *
     * @return float 
     */
    public function getMaintenanceAmount()
    {
        return $this->maintenanceAmount;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return MaintenanceRecord
     */
//    public function setStatus($status)
//    {
//        $this->status = $status;
//
//        return $this;
//    }

    /**
     * Get status
     *
     * @return integer 
     */
//    public function getStatus()
//    {
//        return $this->status;
//    }

    /**
     * Set images
     *
     * @param array $images
     * @return MaintenanceRecord
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Get images
     *
     * @return array 
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return MaintenanceRecord
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
     * @return MaintenanceRecord
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

    /**
     * Set parent
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord $parent
     * @return MaintenanceRecord
     */
    public function setParent(\Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return MaintenanceRecord
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
}
