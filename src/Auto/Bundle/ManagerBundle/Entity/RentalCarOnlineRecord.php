<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


//1: 车辆外观已清洁
//2: 车辆轮胎完好*
//3: 车辆内饰已清洁
//4：保单复印件已有*
//5：车辆行驶本已有*
//6：车辆交强险标志存在*
//7：车辆年检标志存在*
//8：车辆备胎已有*
//9：车辆换胎工具已有*
//10：车辆充电线已有
//11：车辆控制设备可用*
//
//下线：
//12：设备故障
//13：车辆充电
//14：车辆故障/事故
//15：调配车辆
//16:用户还车
//17:人工还车


/**
 * RentalCarOnlineRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RentalCarOnlineRecord
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
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @var RentalCar
     *
     * @ORM\ManyToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var array
     *
     * @ORM\Column(name="reason", type="json_array",nullable=true)
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255,nullable=true)
     */
    private $remark;

    /**
     * @var string
     *
     * @ORM\Column(name="backRange", type="decimal",scale=2,nullable=true)
     */
    private $backRange;



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
     * @return RentalCarOnlineRecord
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
     * Set status
     *
     * @param integer $status
     * @return RentalCarOnlineRecord
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
     * Set reason
     *
     * @param array $reason
     * @return RentalCarOnlineRecord
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return array 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set remark
     *
     * @param string $remark
     * @return RentalCarOnlineRecord
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
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return RentalCarOnlineRecord
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
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return RentalCarOnlineRecord
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
     * Set backRange
     *
     * @param string $backRange
     * @return RentalCarOnlineRecord
     */
    public function setBackRange($backRange)
    {
        $this->backRange = $backRange;

        return $this;
    }

    /**
     * Get backRange
     *
     * @return string 
     */
    public function getBackRange()
    {
        return $this->backRange;
    }
}
