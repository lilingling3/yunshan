<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * IllegalRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class IllegalRecord
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
     * @var string
     *
     * @ORM\Column(name="illegal", type="string", length=255)
     */
    private $illegal;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="RentalOrder")
     */
    private $order;

    /**
     * @var RentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="handleTime", type="datetime",nullable=true)
     */
    private $handleTime;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="illegalTime", type="datetime")
     */
    private $illegalTime;
    
    /**
     * @var DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="illegalScore", type="integer")
     */
    private $illegalScore;

    /**
     * @var string
     *
     * @ORM\Column(name="illegalPlace", type="string", length=255)
     */
    private $illegalPlace;

    /**
     * @var float
     *
     * @ORM\Column(name="illegalAmount", type="float",nullable=true)
     */
    private $illegalAmount;
    
    /**
     * @var Agent
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $agent;

    /**
     * @var float
     *
     * @ORM\Column(name="agentAmount", type="float",nullable=true)
     */
    private $agentAmount;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="agentTime", type="datetime",nullable=true)
     */
    private $agentTime;


    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255,nullable=true)
     */
    private $remark;


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
     * Set illegal
     *
     * @param string $illegal
     * @return IllegalRecord
     */
    public function setIllegal($illegal)
    {
        $this->illegal = $illegal;

        return $this;
    }

    /**
     * Get illegal
     *
     * @return string 
     */
    public function getIllegal()
    {
        return $this->illegal;
    }

    /**
     * Set handleTime
     *
     * @param \DateTime $handleTime
     * @return IllegalRecord
     */
    public function setHandleTime($handleTime)
    {
        $this->handleTime = $handleTime;

        return $this;
    }

    /**
     * Get handleTime
     *
     * @return \DateTime 
     */
    public function getHandleTime()
    {
        return $this->handleTime;
    }

    /**
     * Set illegalTime
     *
     * @param \DateTime $illegalTime
     * @return IllegalRecord
     */
    public function setIllegalTime($illegalTime)
    {
        $this->illegalTime = $illegalTime;

        return $this;
    }

    /**
     * Get illegalTime
     *
     * @return \DateTime 
     */
    public function getIllegalTime()
    {
        return $this->illegalTime;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return IllegalRecord
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
     * Set illegalScore
     *
     * @param integer $illegalScore
     * @return IllegalRecord
     */
    public function setIllegalScore($illegalScore)
    {
        $this->illegalScore = $illegalScore;

        return $this;
    }

    /**
     * Get illegalScore
     *
     * @return integer 
     */
    public function getIllegalScore()
    {
        return $this->illegalScore;
    }

    /**
     * Set illegalPlace
     *
     * @param string $illegalPlace
     * @return IllegalRecord
     */
    public function setIllegalPlace($illegalPlace)
    {
        $this->illegalPlace = $illegalPlace;

        return $this;
    }

    /**
     * Get illegalPlace
     *
     * @return string 
     */
    public function getIllegalPlace()
    {
        return $this->illegalPlace;
    }

    /**
     * Set illegalAmount
     *
     * @param float $illegalAmount
     * @return IllegalRecord
     */
    public function setIllegalAmount($illegalAmount)
    {
        $this->illegalAmount = $illegalAmount;

        return $this;
    }

    /**
     * Get illegalAmount
     *
     * @return float 
     */
    public function getIllegalAmount()
    {
        return $this->illegalAmount;
    }

    /**
     * Set agentAmount
     *
     * @param float $agentAmount
     * @return IllegalRecord
     */
    public function setAgentAmount($agentAmount)
    {
        $this->agentAmount = $agentAmount;

        return $this;
    }

    /**
     * Get agentAmount
     *
     * @return float 
     */
    public function getAgentAmount()
    {
        return $this->agentAmount;
    }

    /**
     * Set agentTime
     *
     * @param \DateTime $agentTime
     * @return IllegalRecord
     */
    public function setAgentTime($agentTime)
    {
        $this->agentTime = $agentTime;

        return $this;
    }

    /**
     * Get agentTime
     *
     * @return \DateTime 
     */
    public function getAgentTime()
    {
        return $this->agentTime;
    }

    /**
     * Set remark
     *
     * @param string $remark
     * @return IllegalRecord
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
     * Set order
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalOrder $order
     * @return IllegalRecord
     */
    public function setOrder(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalOrder 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return IllegalRecord
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

    /**
     * Set agent
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $agent
     * @return IllegalRecord
     */
    public function setAgent(\Auto\Bundle\ManagerBundle\Entity\Member $agent = null)
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * Get agent
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getAgent()
    {
        return $this->agent;
    }
}
