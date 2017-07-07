<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Zhima
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Zhima
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
     * @var integer
     *
     * @ORM\Column(name="member_id", type="integer")
     */
    private $memberId;

    /**
     * @var string
     *
     * @ORM\Column(name="is_admittance", type="string", length=10)
     */
    private $isAdmittance;

    /**
     * @var string
     *
     * @ORM\Column(name="biz_no", type="string", length=255)
     */
    private $bizNo;


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
     * Set memberId
     *
     * @param integer $memberId
     * @return Zhima
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;

        return $this;
    }

    /**
     * Get memberId
     *
     * @return integer
     */
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * Set isAdmittance
     *
     * @param string $isAdmittance
     * @return Zhima
     */
    public function setIsAdmittance($isAdmittance)
    {
        $this->isAdmittance = $isAdmittance;

        return $this;
    }

    /**
     * Get isAdmittance
     *
     * @return string
     */
    public function getIsAdmittance()
    {
        return $this->isAdmittance;
    }

    /**
     * Set bizNo
     *
     * @param string $bizNo
     * @return Zhima
     */
    public function setBizNo($bizNo)
    {
        $this->bizNo = $bizNo;

        return $this;
    }

    /**
     * Get bizNo
     *
     * @return string
     */
    public function getBizNo()
    {
        return $this->bizNo;
    }
}
