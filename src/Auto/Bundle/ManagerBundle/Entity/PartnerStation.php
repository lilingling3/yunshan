<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Partner
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class PartnerStation
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
     * @var Station
     *
     * @ORM\ManyToOne(targetEntity="Station")
     */
    private $station;

    /**
     * @var string
     *
     * @ORM\Column(name="partnerStation", type="integer")
     */
    private $partnerStation;

    /**
     * @var Partner
     *
     * @ORM\ManyToOne(targetEntity="Partner")
     */
    private $partner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

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
     * @return PartnerStation
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
     * Set status
     *
     * @param integer $partnerStation
     * @return PartnerStation
     */
    public function setPartnerStation($partnerStation)
    {
        $this->partnerStation = $partnerStation;

        return $this;
    }

    /**
     * Get PartnerStation
     *
     * @return integer
     */
    public function getPartnerStation()
    {
        return $this->partnerStation;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return PartnerStation
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set Partner
     *
     * @param Partner $partner
     * @return PartnerStation
     */
    public function setPartner(Partner $partner)
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * Get Partner
     *
     * @return Partner
     */
    public function getPartner()
    {
        return $this->partner;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Station $station
     * @return PartnerStation
     */
    public function setStation(\Auto\Bundle\ManagerBundle\Entity\Station $station = null)
    {
        $this->station = $station;

        return $this;
    }

    /**
     * Get member
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Station
     */
    public function getStation()
    {
        return $this->station;
    }

}

