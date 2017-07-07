<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Upkeep
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Upkeep
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
     * @ORM\Column(name="upkeepTime", type="datetime")
     */
    private $upkeepTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="nextUpkeepTime", type="datetime")
     */
    private $nextUpkeepTime;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255,nullable=true)
     */
    private $remark;

    /**
     * @var RentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var string
     *
     * @ORM\Column(name="nextMileage", type="decimal",scale=2,nullable=true)
     */
    private $nextMileage;
    

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
     * @return Upkeep
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
     * Set upkeepTime
     *
     * @param \DateTime $upkeepTime
     * @return Upkeep
     */
    public function setUpkeepTime($upkeepTime)
    {
        $this->upkeepTime = $upkeepTime;

        return $this;
    }

    /**
     * Get upkeepTime
     *
     * @return \DateTime 
     */
    public function getUpkeepTime()
    {
        return $this->upkeepTime;
    }

    /**
     * Set nextUpkeepTime
     *
     * @param \DateTime $nextUpkeepTime
     * @return Upkeep
     */
    public function setNextUpkeepTime($nextUpkeepTime)
    {
        $this->nextUpkeepTime = $nextUpkeepTime;

        return $this;
    }

    /**
     * Get nextUpkeepTime
     *
     * @return \DateTime 
     */
    public function getNextUpkeepTime()
    {
        return $this->nextUpkeepTime;
    }

    /**
     * Set remark
     *
     * @param string $remark
     * @return Upkeep
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
     * Set nextMileage
     *
     * @param string $nextMileage
     * @return Upkeep
     */
    public function setNextMileage($nextMileage)
    {
        $this->nextMileage = $nextMileage;

        return $this;
    }

    /**
     * Get nextMileage
     *
     * @return string 
     */
    public function getNextMileage()
    {
        return $this->nextMileage;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return Upkeep
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
