<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class WalletRecord
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
     * @var RentalOrder
     *
     * @ORM\ManyToOne(targetEntity="RentalOrder")
     */
    private $rentalOrder;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

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
     * @return WalletRecord
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return WalletRecord
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
     * Set rentalOrder
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalOrder $rentalOrder
     * @return WalletRecord
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
}
