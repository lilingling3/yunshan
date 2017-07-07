<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Station
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 */
class Station
{
    const STATION_ONLINE = 801;//上线
    const STATION_OFFLINE = 800;//下线

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
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="Area")
     */
    private $area;

    /**
     * @var string
     *
     * @ORM\Column(name="street", type="string", length=255,nullable=true)
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="decimal",scale=6,nullable=true)
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="decimal",scale=6,nullable=true)
     */
    private $longitude;

    /**
     * @var integer
     *
     * @ORM\Column(name="online", type="integer",options={"default"=0})
     */
    private $online;

    /**
     * @var BusinessDistrict
     *
     * @ORM\ManyToOne(targetEntity="BusinessDistrict")
     */
    private $businessDistrict;

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
     * @return RentalStation
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
     * Set area
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Area
     * @return Station
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
     * Set street
     *
     * @param string $street
     * @return Station
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string 
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     * @return Station
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string 
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return Station
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string 
     */
    public function getLongitude()
    {
        return $this->longitude;
    }


    /**
     * Set online
     *
     * @param integer $online
     * @return Station
     */
    public function setOnline($online)
    {
        $this->online = $online;

        return $this;
    }

    /**
     * Get online
     *
     * @return integer
     */
    public function getOnline()
    {
        return $this->online;
    }
    /**
     * Set BusinessDistrict
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\BusinessDistrict
     * @return Station
     */
    public function setBusinessDistrict(\Auto\Bundle\ManagerBundle\Entity\BusinessDistrict $businessDistrict = null)
    {
        $this->businessDistrict = $businessDistrict;

        return $this;
    }

    /**
     * Get businessDistrict
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\BusinessDistrict
     */
    public function getBusinessDistrict()
    {
        return $this->businessDistrict;
    }
}
