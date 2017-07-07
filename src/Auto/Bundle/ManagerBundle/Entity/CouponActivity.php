<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * couponActivity
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class CouponActivity
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="CouponKind")
     * @ORM\OrderBy({"id"="ASC"})
     */
    private $kinds;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255,nullable=true)
     */
    private $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="count", type="integer")
     */
    private $count;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="BaseOrder")
     */
    private $order;
    
    /**
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @var integer
     *
     * @ORM\Column(name="total", type="integer",nullable=true,options={"default"=0})
     */
    private $total;

    /**
     * @var integer
     *
     * @ORM\Column(name="online", type="smallint")
     */
    private $online;

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
     * Set name
     *
     * @param string $name
     * @return CouponActivity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set count
     *
     * @param integer $count
     * @return CouponActivity
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set online
     *
     * @param integer $online
     * @return CouponActivity
     */
    public function setOnline($online)
    {
        $this->online = $online;

        return $this;
    }

    /**
     * Get online
     *
     * @return integer 
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return CouponActivity
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
     * Add kinds
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\CouponKind $kinds
     * @return CouponActivity
     */
    public function addKind(\Auto\Bundle\ManagerBundle\Entity\CouponKind $kinds)
    {
        $this->kinds[] = $kinds;

        return $this;
    }

    /**
     * Remove kinds
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\CouponKind $kinds
     */
    public function removeKind(\Auto\Bundle\ManagerBundle\Entity\CouponKind $kinds)
    {
        $this->kinds->removeElement($kinds);
    }

    /**
     * Get kinds
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getKinds()
    {
        return $this->kinds;
    }

    /**
     * Set total
     *
     * @param integer $total
     * @return CouponActivity
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return integer 
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set order
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\BaseOrder $order
     * @return CouponActivity
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

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return CouponActivity
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
     * Set code
     *
     * @param string $code
     * @return CouponActivity
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }
}
