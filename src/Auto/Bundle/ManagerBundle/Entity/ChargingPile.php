<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChargingPile
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ChargingPile
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
     * @var integer
     *
     * @ORM\Column(name="No", type="integer")
     */
    private $no;

    /**
     * @var string
     *
     * @ORM\Column(name="Ident", type="string", length=255)
     */
    private $ident;

    /**
     * @var Station
     *
     * @ORM\ManyToOne(targetEntity="Station")
     */
    private $station;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;


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
     * Set no
     *
     * @param integer $no
     * @return ChargingPile
     */
    public function setNo($no)
    {
        $this->no = $no;

        return $this;
    }

    /**
     * Get no
     *
     * @return integer 
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * Set ident
     *
     * @param string $ident
     * @return ChargingPile
     */
    public function setIdent($ident)
    {
        $this->ident = $ident;

        return $this;
    }

    /**
     * Get ident
     *
     * @return string 
     */
    public function getIdent()
    {
        return $this->ident;
    }

    /**
     * Set station
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Station $station
     * @return ChargingPile
     */
    public function setStation(\Auto\Bundle\ManagerBundle\Entity\Station $station = null)
    {
        $this->station = $station;

        return $this;
    }

    /**
     * Get station
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Station 
     */
    public function getStation()
    {
        return $this->station;
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

}
