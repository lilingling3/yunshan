<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RentalPrice
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RentalPrice
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
     * @ORM\Column(name="name", type="string", length=255,nullable=true)
     */
    private $name;
    
    /**
     * @var Car
     *
     * @ORM\ManyToOne(targetEntity="Car")
     */
    private $car;

    /**
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="Area")
     */
    private $area;

    /**
     * @var float
     *
     * @ORM\Column(name="Price", type="float")
     */

    private $price;


    /**
     * @var \Time
     *
     * @ORM\Column(name="startTime", type="datetime",nullable=true)
     */

    private $startTime;

    /**
     * @var \Time
     *
     * @ORM\Column(name="endTime", type="datetime",nullable=true)
     */

    private $endTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="smallint",options={"default"=0},nullable=true)
     */
    private $weight;

    /**
     * @var integer
     *
     * @ORM\Column(name="maxHour", type="integer",options={"default"=0},nullable=true)
     */
    private $maxHour;

    /**
     * @var integer
     *
     * @ORM\Column(name="minHour", type="integer",options={"default"=0},nullable=true)
     */
    private $minHour;


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
     * Set price
     *
     * @param float $price
     * @return RentalPrice
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }


    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return RentalPrice
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
     * @return RentalPrice
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
     * Set car
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Car $car
     * @return RentalPrice
     */
    public function setCar(\Auto\Bundle\ManagerBundle\Entity\Car $car = null)
    {
        $this->car = $car;

        return $this;
    }

    /**
     * Get car
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Car 
     */
    public function getCar()
    {
        return $this->car;
    }

    /**
     * Set area
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Area $area
     * @return RentalPrice
     */
    public function setArea(\Auto\Bundle\ManagerBundle\Entity\Area $area = null)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Area 
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return RentalPrice
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
     * Set weight
     *
     * @param integer $weight
     * @return RentalPrice
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set maxHour
     *
     * @param integer $maxHour
     * @return RentalPrice
     */
    public function setMaxHour($maxHour)
    {
        $this->maxHour = $maxHour;

        return $this;
    }

    /**
     * Get maxHour
     *
     * @return integer 
     */
    public function getMaxHour()
    {
        return $this->maxHour;
    }

    /**
     * Set minHour
     *
     * @param integer $minHour
     * @return RentalPrice
     */
    public function setMinHour($minHour)
    {
        $this->minHour = $minHour;

        return $this;
    }

    /**
     * Get minHour
     *
     * @return integer 
     */
    public function getMinHour()
    {
        return $this->minHour;
    }
}
