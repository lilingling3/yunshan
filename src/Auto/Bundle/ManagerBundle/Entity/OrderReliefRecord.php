<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderReliefRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OrderReliefRecord
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
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;


    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="BaseOrder")
     */
    private $order;

    /**
     * @var operateMember
     *
     * @ORM\OneToOne(targetEntity="Member")
     */
    private $operateMember;

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
     * Set amount
     *
     * @param float $amount
     * @return OrderReliefRecord
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set operateMember
     *
     * @param \stdClass $operateMember
     * @return OrderReliefRecord
     */
    public function setOperateMember($operateMember)
    {
        $this->operateMember = $operateMember;

        return $this;
    }

    /**
     * Get operateMember
     *
     * @return \stdClass 
     */
    public function getOperateMember()
    {
        return $this->operateMember;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return OrderReliefRecord
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
     * Set order
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\BaseOrder $order
     * @return OrderReliefRecord
     */
    public function setOrder(\Auto\Bundle\ManagerBundle\Entity\BaseOrder $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\BaseOrder 
     */
    public function getOrder()
    {
        return $this->order;
    }
}
