<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Remind
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Remind
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
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $member;

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
     * @var \DateTime
     *
     * @ORM\Column(name="remindTime", type="datetime",nullable=true)
     */
    private $remindTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endTime", type="datetime")
     */
    private $endTime;


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
     * @return Remind
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
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return Remind
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime 
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set remindTime
     *
     * @param \DateTime $remindTime
     * @return Remind
     */
    public function setRemindTime($remindTime)
    {
        $this->remindTime = $remindTime;

        return $this;
    }

    /**
     * Get remindTime
     *
     * @return \DateTime
     */
    public function getRemindTime()
    {
        return $this->remindTime;
    }


    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return Remind
     */
    public function setMember(\Auto\Bundle\ManagerBundle\Entity\Member $member = null)
    {
        $this->member = $member;

        return $this;
    }

    /**
     * Get member
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set rentalStation
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation
     * @return Remind
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
}
