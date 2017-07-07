<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RentalStationDiscount
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RentalStationDiscount
{
    //取车折扣
    const RENTAL_STATION_DISCOUNT_KIND_ONE      = 1;
    //还车折扣
    const RENTAL_STATION_DISCOUNT_KIND_TWO      = 2;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

//    /**
//     * @var \stdClass
//     *
//     * @ORM\Column(name="RentalStation", type="object")
//     */
//    private $rentalStation;

    /**
     * @var RentalStation
     *
     * @ORM\ManyToOne(targetEntity="RentalStation")
     */
    private $rentalStation;

    /**
     * @var integer
     *
     * @ORM\Column(name="kind", type="smallint")
     */
    private $kind;//'1' => '取车折扣', '2' => '还车折扣'

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="float")
     */
    private $discount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startTime", type="datetime")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endTime", type="datetime")
     */
    private $endTime;


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
     * Set kind
     *
     * @param integer $kind
     * @return RentalStationDiscount
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return RentalStationDiscount
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
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return RentalStationDiscount
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
     * @return RentalStationDiscount
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
     * Set discount
     *
     * @param float $discount
     * @return RentalStationDiscount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return float 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set rentalStation
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation
     * @return RentalStationDiscount
     */
    public function setRentalStation(\Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation = null)
    {
        $this->rentalStation = $rentalStation;

        return $this;
    }

    /**
     * Get rentalStation
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalStation 
     */
    public function getRentalStation()
    {
        return $this->rentalStation;
    }
}
