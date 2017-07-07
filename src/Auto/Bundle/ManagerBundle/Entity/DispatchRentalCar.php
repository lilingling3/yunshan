<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DispatchRentalCar
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class DispatchRentalCar
{
    const OPERATOR_DISPATCH_CAR_KIND = 1;   //运营人员调配
    const USER_RETURN_CAR_KIND = 2;   //用户订单

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
     * @var RentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var RentalOrder
     *
     * @ORM\ManyToOne(targetEntity="RentalOrder")
     */
    private $rentalOrder;

    /**
     * @var RentalStation
     *
     * @ORM\ManyToOne(targetEntity="RentalStation")
     */
    private $rentalStation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="kind", type="smallint")
     */
    private $kind;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint",nullable=true,options={"default"=0})
     */
    private $status;

    /**
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $operateMember;
    
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
     * @return DispatchRentalCar
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
     * Set kind
     *
     * @param integer $kind
     * @return DispatchRentalCar
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
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return DispatchRentalCar
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
     * Set rentalStation
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation
     * @return DispatchRentalCar
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

    /**
     * Set rentalOrder
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalOrder $rentalOrder
     * @return DispatchRentalCar
     */
    public function setRentalOrder(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $rentalOrder = null)
    {
        $this->rentalOrder = $rentalOrder;

        return $this;
    }

    /**
     * Get rentalOrder
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalOrder 
     */
    public function getRentalOrder()
    {
        return $this->rentalOrder;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return DispatchRentalCar
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
     * Set operateMember
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $operateMember
     * @return DispatchRentalCar
     */
    public function setOperateMember(\Auto\Bundle\ManagerBundle\Entity\Member $operateMember = null)
    {
        $this->operateMember = $operateMember;

        return $this;
    }

    /**
     * Get operateMember
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getOperateMember()
    {
        return $this->operateMember;
    }
}
