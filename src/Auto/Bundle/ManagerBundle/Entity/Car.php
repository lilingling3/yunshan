<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Car
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Car
{
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
     * @var string
     *
     * @ORM\Column(name="brand", type="string", length=50, nullable=true)
     */
    private $brand;

    /**
     * @var integer
     *
     * @ORM\Column(name="startType", type="integer",nullable=true)
     */
    private $startType;

    /**
     * @var integer
     *
     * @ORM\Column(name="length", type="integer")
     */
    private $length;

    /**
     * @var integer
     *
     * @ORM\Column(name="width", type="integer")
     */
    private $width;

    /**
     * @var integer
     *
     * @ORM\Column(name="height", type="integer")
     */
    private $height;

    /**
     * @var integer
     *
     * @ORM\Column(name="doors", type="integer")
     */
    private $doors;


    /**
     * @var float
     *
     * @ORM\Column(name="battery", type="float",nullable=true)
     */
    private $battery;

    /**
     * @var integer
     *
     * @ORM\Column(name="seats", type="integer")
     */
    private $seats;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="text",nullable=true)
     */
    private $image;

    /**
     * @var BodyType
     *
     * @ORM\ManyToOne(targetEntity="BodyType")
     */

    private $bodyType;

    /**
     * @var CarLevel
     *
     * @ORM\ManyToOne(targetEntity="CarLevel")
     */
    private $level;

    /**
     * @var integer
     *
     * @ORM\Column(name="airbags", type="integer",nullable=true)
     */
    private $airbags;

    /**
     * @var string
     *
     * @ORM\Column(name="driveMileage", type="decimal",scale=2)
     */
    private $driveMileage;

    /**
     * @var integer
     *
     * @ORM\Column(name="autoOfflineMileage", type="integer",options={"default"=30},nullable=true)
     */
    private $autoOfflineMileage;

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
     * @return Car
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
     * Set length
     *
     * @param integer $length
     * @return Car
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return integer 
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set width
     *
     * @param integer $width
     * @return Car
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return Car
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set doors
     *
     * @param integer $doors
     * @return Car
     */
    public function setDoors($doors)
    {
        $this->doors = $doors;

        return $this;
    }

    /**
     * Get doors
     *
     * @return integer 
     */
    public function getDoors()
    {
        return $this->doors;
    }

    /**
     * Set seats
     *
     * @param integer $seats
     * @return Car
     */
    public function setSeats($seats)
    {
        $this->seats = $seats;

        return $this;
    }

    /**
     * Get seats
     *
     * @return integer 
     */
    public function getSeats()
    {
        return $this->seats;
    }

    /**
     * Set bodyType
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\BodyType $bodyType
     * @return Car
     */
    public function setBodyType(\Auto\Bundle\ManagerBundle\Entity\BodyType $bodyType = null)
    {
        $this->bodyType = $bodyType;

        return $this;
    }

    /**
     * Get bodyType
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\BodyType 
     */
    public function getBodyType()
    {
        return $this->bodyType;
    }

    /**
     * Set level
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\CarLevel $level
     * @return Car
     */
    public function setLevel(\Auto\Bundle\ManagerBundle\Entity\CarLevel $level = null)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\CarLevel 
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set airbags
     *
     * @param integer $airbags
     * @return Car
     */
    public function setAirbags($airbags)
    {
        $this->airbags = $airbags;

        return $this;
    }

    /**
     * Get airbags
     *
     * @return integer 
     */
    public function getAirbags()
    {
        return $this->airbags;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return Car
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set driveMileage
     *
     * @param string $driveMileage
     * @return Car
     */
    public function setDriveMileage($driveMileage)
    {
        $this->driveMileage = $driveMileage;

        return $this;
    }

    /**
     * Get driveMileage
     *
     * @return string 
     */
    public function getDriveMileage()
    {
        return $this->driveMileage;
    }

    /**
     * Set autoOfflineMileage
     *
     * @param string $autoOfflineMileage
     * @return Car
     */
    public function setAutoOfflineMileage($autoOfflineMileage)
    {
        $this->autoOfflineMileage = $autoOfflineMileage;

        return $this;
    }

    /**
     * Get autoOfflineMileage
     *
     * @return string
     */
    public function getAutoOfflineMileage()
    {
        return $this->autoOfflineMileage;
    }


    /**
     * Set battery
     *
     * @param float $battery
     * @return Car
     */
    public function setBattery($battery)
    {
        $this->battery = $battery;

        return $this;
    }

    /**
     * Get battery
     *
     * @return float 
     */
    public function getBattery()
    {
        return $this->battery;
    }

    /**
     * Set brand
     *
     * @param string $brand
     * @return Car
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get brand
     *
     * @return string 
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Set startType
     *
     * @param integer $startType
     * @return Car
     */
    public function setStartType($startType)
    {
        $this->startType = $startType;

        return $this;
    }

    /**
     * Get startType
     *
     * @return integer 
     */
    public function getStartType()
    {
        return $this->startType;
    }
}
