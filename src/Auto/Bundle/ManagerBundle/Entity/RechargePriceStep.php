<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RechargePriceStep
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RechargePriceStep
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var activity
     *
     * @ORM\ManyToOne(targetEntity="RechargeActivity")
     */
    private $activity;

    /**
     * @var integer
     *
     * @ORM\Column(name="step", type="smallint",options={"default"=0},nullable=true)
     */
    private $step;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float",nullable=true)
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="cashback", type="float",nullable=true)
     */
    private $cashback;

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
     * Set activity
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RechargeActivity $activity
     * @return RechargePriceStep
     */
    public function setActivity(\Auto\Bundle\ManagerBundle\Entity\RechargeActivity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RechargeActivity 
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set step
     *
     * @param integer $step
     * @return RechargePriceStep
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Get step
     *
     * @return integer 
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return RechargePriceStep
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set cashback
     *
     * @param float $cashback
     * @return RechargePriceStep
     */
    public function setCashBack($cashback)
    {
        $this->cashback = $cashback;

        return $this;
    }

    /**
     * Get cashback
     *
     * @return float 
     */
    public function getCashBack()
    {
        return $this->cashback;
    }
}


