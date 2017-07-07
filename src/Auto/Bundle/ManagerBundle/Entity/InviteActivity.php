<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints;

/**
 * InviteReward
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class InviteActivity
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
     * @ORM\ManyToOne(targetEntity="CouponKind")
     */
    private $kind;

    /**
     * @var cashback
     *
     * @ORM\Column(name="cashback", type="integer")
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
     * Set kind
     *
     * @param string $kind
     * @return InviteActivity
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind
     *
     * @return string 
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set cashback
     *
     * @param integer $cashback
     * @return InviteActivity
     */
    public function setCashBack($cashback)
    {
        $this->cashback = $cashback;

        return $this;
    }

    /**
     * Get cashback
     *
     * @return integer 
     */
    public function getCashBack()
    {
        return $this->cashback;
    }

}
