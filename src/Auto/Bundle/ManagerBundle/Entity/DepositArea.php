<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DepositArea
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class DepositArea
{
    // const APPEAL_SUCCESS = 1;
    // const APPEAL_FAIL = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="Area")
     */
    private $area;

    /**
     * @var integer
     *
     * @ORM\Column(name="isneed_deposit", type="smallint")
     */
    private $isneedDeposit;

    /**
     * @var float
     *
     * @ORM\Column(name="needdepositamount", type="float")
     */
    private $needDeposit;


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
     * Set isneedDeposit
     *
     * @param integer $isneedDeposit
     * @return DepositArea
     */
    public function setIsNeed2Deposit($isneedDeposit)
    {
        $this->isneedDeposit = $isneedDeposit;

        return $this;
    }

    /**
     * Get isneedDeposit
     *
     * @return integer
     */
    public function getIsNeed2Deposit()
    {
        return $this->isneedDeposit;
    }

    /**
     * Set area
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Area $area
     * @return DepositArea
     */
    public function setArea(\Auto\Bundle\ManagerBundle\Entity\Area $area = null)
    {
        $this->area = $area;
        return $this;
    }

    /**
     * Get area
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Area
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set needDeposit
     *
     * @param float $deposit
     * @return DepositArea
     */
    public function setNeedDepositAmount($deposit)
    {
        $this->needDeposit = $deposit;

        return $this;
    }

    /**
     * Get needDeposit
     *
     * @return float 
     */
    public function getNeedDepositAmount()
    {
        return $this->needDeposit;
    }
}
