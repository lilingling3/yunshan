<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints;

/**
 * OperatorAttendanceRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OperatorAttendanceRecord
{
    const STATUS_ON_DUTY = 1;  //上班
    const STATUS_OFF_DUTY = 2;  //下班
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
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="decimal",scale=6,nullable=true)
     */
    private $latitude;//纬度

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="decimal",scale=6,nullable=true)
     */
    private $longitude;//经度

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer",nullable=true)
     */
    private $status;//状态：上班还是下班


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createTime = new \DateTime();
    }



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
     * @return OperatorAttendanceRecord
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
     * Set latitude
     *
     * @param string $latitude
     * @return OperatorAttendanceRecord
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string 
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return OperatorAttendanceRecord
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string 
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return OperatorAttendanceRecord
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
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return OperatorAttendanceRecord
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
}
