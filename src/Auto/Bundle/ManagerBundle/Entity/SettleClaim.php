<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SettleClaim
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class SettleClaim
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
     * @var string
     *
     * @ORM\Column(name="claimLicensePlate", type="string", length=50)
     */
    private $claimLicensePlate;//理赔车车牌号

    /**
     * @var string
     *
     * @ORM\Column(name="downReason", type="string", length=255, nullable=true)
     */
    private $downReason;//事故原因

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="downTime", type="datetime", nullable=true)
     */
    private $downTime;//事故时间

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="applyTime", type="datetime", nullable=true)
     */
    private $applyTime;//报案时间

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="settleTime", type="datetime", nullable=true)
     */
    private $settleTime;//交案时间

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="claimTime", type="datetime", nullable=true)
     */
    private $claimTime;//理赔时间

    /**
     * @var float
     *
     * @ORM\Column(name="claimAmount", type="float")
     */
    private $claimAmount;//理赔金额


    /**
     * @var array
     *
     * @ORM\Column(name="images", type="json_array",nullable=true)
     */
    private $images;

    /**
     * @var maintenanceRecord
     *
     * @ORM\ManyToOne(targetEntity="MaintenanceRecord")
     */

    private $maintenanceRecord;



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
     * @return SettleClaim
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
     * Set claimLicensePlate
     *
     * @param string $claimLicensePlate
     * @return SettleClaim
     */
    public function setClaimLicensePlate($claimLicensePlate)
    {
        $this->claimLicensePlate = $claimLicensePlate;

        return $this;
    }

    /**
     * Get claimLicensePlate
     *
     * @return string 
     */
    public function getClaimLicensePlate()
    {
        return $this->claimLicensePlate;
    }

    /**
     * Set downReason
     *
     * @param string $downReason
     * @return SettleClaim
     */
    public function setDownReason($downReason)
    {
        $this->downReason = $downReason;

        return $this;
    }

    /**
     * Get downReason
     *
     * @return string 
     */
    public function getDownReason()
    {
        return $this->downReason;
    }

    /**
     * Set downTime
     *
     * @param \DateTime $downTime
     * @return SettleClaim
     */
    public function setDownTime($downTime)
    {
        $this->downTime = $downTime;

        return $this;
    }

    /**
     * Get downTime
     *
     * @return \DateTime 
     */
    public function getDownTime()
    {
        return $this->downTime;
    }

    /**
     * Set applyTime
     *
     * @param \DateTime $applyTime
     * @return SettleClaim
     */
    public function setApplyTime($applyTime)
    {
        $this->applyTime = $applyTime;

        return $this;
    }

    /**
     * Get applyTime
     *
     * @return \DateTime 
     */
    public function getApplyTime()
    {
        return $this->applyTime;
    }

    /**
     * Set settleTime
     *
     * @param \DateTime $settleTime
     * @return SettleClaim
     */
    public function setSettleTime($settleTime)
    {
        $this->settleTime = $settleTime;

        return $this;
    }

    /**
     * Get settleTime
     *
     * @return \DateTime 
     */
    public function getSettleTime()
    {
        return $this->settleTime;
    }

    /**
     * Set claimTime
     *
     * @param \DateTime $claimTime
     * @return SettleClaim
     */
    public function setClaimTime($claimTime)
    {
        $this->claimTime = $claimTime;

        return $this;
    }

    /**
     * Get claimTime
     *
     * @return \DateTime 
     */
    public function getClaimTime()
    {
        return $this->claimTime;
    }

    /**
     * Set claimAmount
     *
     * @param float $claimAmount
     * @return SettleClaim
     */
    public function setClaimAmount($claimAmount)
    {
        $this->claimAmount = $claimAmount;

        return $this;
    }

    /**
     * Get claimAmount
     *
     * @return float 
     */
    public function getClaimAmount()
    {
        return $this->claimAmount;
    }

    /**
     * Set images
     *
     * @param array $images
     * @return SettleClaim
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
     * Set maintenanceRecord
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord $maintenanceRecord
     * @return SettleClaim
     */
    public function setMaintenanceRecord(\Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord $maintenanceRecord = null)
    {
        $this->maintenanceRecord = $maintenanceRecord;

        return $this;
    }

    /**
     * Get maintenanceRecord
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord 
     */
    public function getMaintenanceRecord()
    {
        return $this->maintenanceRecord;
    }
}
