<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints;


/**
 * RentalStation
 *
 * @ORM\Table()
 * @ORM\Entity
 * @Constraints\GroupSequence({"RentalStation", "commit"})
 */

class RentalStation  extends Station
{
    const SAME_PLACE_BACK = 600;//原地还车
    const DIFFERENT_PLACE_BACK = 601;//异地还车


    public function __construct()
    {
        $this->createTime = new \DateTime();
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var array
     *
     * @ORM\Column(name="images", type="json_array")
     */
    private $images;

    /**
     * @ORM\OneToMany(targetEntity="RentalCar", mappedBy="rentalStation")
     * @ORM\OrderBy({"id"="ASC"})
     */
    private $rentalCars;

    /**
     * @var integer
     *
     * @ORM\Column(name="parkingSpaceTotal", type="integer",nullable=true,options={"default"=0})
     */
    private $parkingSpaceTotal;

    /**
     * @var integer
     *
     * @ORM\Column(name="usableParkingSpace", type="integer",nullable=true,options={"default"=0})
     */
    private $usableParkingSpace;//可停车辆总数

    /**
     * @var integer
     *
     * @ORM\Column(name="backType", type="integer",nullable=true,options={"default"=600})
     */
    private $backType;

    /**
     * @var string
     *
     * @ORM\Column(name="contactMobile", type="string", length=255,nullable=true)
     */
    private $contactMobile;

    /**
     * @var company
     *
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $company;

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return RentalStation
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
     * Set images
     *
     * @param array $images
     * @return RentalStation
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
     * Add rentalCars
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCars
     * @return RentalStation
     */
    public function addRentalCar(\Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCars)
    {
        $this->rentalCars[] = $rentalCars;

        return $this;
    }

    /**
     * Remove rentalCars
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCars
     */
    public function removeRentalCar(\Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCars)
    {
        $this->rentalCars->removeElement($rentalCars);
    }

    /**
     * Get rentalCars
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRentalCars()
    {
        return $this->rentalCars;
    }

    /**
     * Set parkingSpaceTotal
     *
     * @param integer $parkingSpaceTotal
     * @return RentalStation
     */
    public function setParkingSpaceTotal($parkingSpaceTotal)
    {
        $this->parkingSpaceTotal = $parkingSpaceTotal;

        return $this;
    }

    /**
     * Get parkingSpaceTotal
     *
     * @return integer 
     */
    public function getParkingSpaceTotal()
    {
        return $this->parkingSpaceTotal;
    }

    /**
     * Set backType
     *
     * @param integer $backType
     * @return RentalStation
     */
    public function setBackType($backType)
    {
        $this->backType = $backType;

        return $this;
    }

    /**
     * Get backType
     *
     * @return integer 
     */
    public function getBackType()
    {
        return $this->backType;
    }

    /**
     * Set contactMobile
     *
     * @param string $contactMobile
     * @return RentalStation
     */
    public function setContactMobile($contactMobile)
    {
        $this->contactMobile = $contactMobile;

        return $this;
    }

    /**
     * Get contactMobile
     *
     * @return string 
     */
    public function getContactMobile()
    {
        return $this->contactMobile;
    }

    /**
     * Set company
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Company $company
     * @return RentalStation
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
     * Set usableParkingSpace
     *
     * @param integer $usableParkingSpace
     * @return RentalStation
     */
    public function setUsableParkingSpace($usableParkingSpace)
    {
        $this->usableParkingSpace = $usableParkingSpace;

        return $this;
    }

    /**
     * Get usableParkingSpace
     *
     * @return integer 
     */
    public function getUsableParkingSpace()
    {
        return $this->usableParkingSpace;
    }
}
