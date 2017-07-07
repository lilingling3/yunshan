<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints;

/**
 * ChargingStation
 *
 * @ORM\Table()
 * @ORM\Entity
 * @Constraints\GroupSequence({"ChargingStation", "commit"})
 */
class ChargingStation  extends Station
{

    /**
     * @var array
     *
     * @ORM\Column(name="images", type="json_array",nullable=true)
     */
    private $images;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime",nullable=true)
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="outId", type="integer",nullable=true)
     */
    private $outId;


    /**
     * @var integer
     *
     * @ORM\Column(name="currentStatus", type="integer",nullable=true)
     */
    private $currentStatus;


    /**
     * @var integer
     *
     * @ORM\Column(name="isActive", type="integer",nullable=true)
     */
    private $isActive;

    /**
     * @var integer
     *
     * @ORM\Column(name="portType", type="integer",nullable=true)
     */
    private $portType;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string",nullable=true)
     */
    private $address;

    /**
     * @var integer
     *
     * @ORM\Column(name="fastCount", type="integer",nullable=true)
     */
    private $fastCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="slowCount", type="integer",nullable=true)
     */
    private $slowCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="nature", type="integer",nullable=true)
     */
    private $nature;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer",nullable=true)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="evCount", type="integer",nullable=true)
     */
    private $evCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="authStatus", type="integer",nullable=true)
     */
    private $authStatus;

    /**
     * Set images
     *
     * @param array $images
     * @return ChargingStation
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return ChargingStation
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
     * Set outId
     *
     * @param integer $outId
     * @return ChargingStation
     */
    public function setOutId($outId)
    {
        $this->outId = $outId;

        return $this;
    }

    /**
     * Get outId
     *
     * @return integer 
     */
    public function getOutId()
    {
        return $this->outId;
    }

    /**
     * Set currentStatus
     *
     * @param integer $currentStatus
     * @return ChargingStation
     */
    public function setCurrentStatus($currentStatus)
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * Get currentStatus
     *
     * @return integer 
     */
    public function getCurrentStatus()
    {
        return $this->currentStatus;
    }

    /**
     * Set isActive
     *
     * @param integer $isActive
     * @return ChargingStation
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return integer 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set portType
     *
     * @param integer $portType
     * @return ChargingStation
     */
    public function setPortType($portType)
    {
        $this->portType = $portType;

        return $this;
    }

    /**
     * Get portType
     *
     * @return integer 
     */
    public function getPortType()
    {
        return $this->portType;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return ChargingStation
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set fastCount
     *
     * @param integer $fastCount
     * @return ChargingStation
     */
    public function setFastCount($fastCount)
    {
        $this->fastCount = $fastCount;

        return $this;
    }

    /**
     * Get fastCount
     *
     * @return integer 
     */
    public function getFastCount()
    {
        return $this->fastCount;
    }

    /**
     * Set slowCount
     *
     * @param integer $slowCount
     * @return ChargingStation
     */
    public function setSlowCount($slowCount)
    {
        $this->slowCount = $slowCount;

        return $this;
    }

    /**
     * Get slowCount
     *
     * @return integer 
     */
    public function getSlowCount()
    {
        return $this->slowCount;
    }

    /**
     * Set nature
     *
     * @param integer $nature
     * @return ChargingStation
     */
    public function setNature($nature)
    {
        $this->nature = $nature;

        return $this;
    }

    /**
     * Get nature
     *
     * @return integer 
     */
    public function getNature()
    {
        return $this->nature;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return ChargingStation
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set evCount
     *
     * @param integer $evCount
     * @return ChargingStation
     */
    public function setEvCount($evCount)
    {
        $this->evCount = $evCount;

        return $this;
    }

    /**
     * Get evCount
     *
     * @return integer 
     */
    public function getEvCount()
    {
        return $this->evCount;
    }

    /**
     * Set authStatus
     *
     * @param integer $authStatus
     * @return ChargingStation
     */
    public function setAuthStatus($authStatus)
    {
        $this->authStatus = $authStatus;

        return $this;
    }

    /**
     * Get authStatus
     *
     * @return integer 
     */
    public function getAuthStatus()
    {
        return $this->authStatus;
    }
}
