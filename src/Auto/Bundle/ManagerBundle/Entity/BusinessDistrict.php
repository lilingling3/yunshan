<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BusinessDistrict
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class BusinessDistrict
{
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
     * @ORM\Column(name="latitude", type="decimal",scale=6,)
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="decimal",scale=6)
     */
    private $longitude;

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return BusinessDistrict
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
     * @return BusinessDistrict
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
     * @return BusinessDistrict
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
     * Set latitude
     *
     * @param string $latitude
     * @return BusinessDistrict
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
     * @return BusinessDistrict
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

}
