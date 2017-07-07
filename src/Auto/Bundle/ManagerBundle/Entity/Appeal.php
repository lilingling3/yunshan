<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Appeal
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Appeal
{
    const APPEAL_SUCCESS = 1;
    const APPEAL_FAIL = 0;

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
     * @var BlackList
     *
     * @ORM\ManyToOne(targetEntity="BlackList")
     */
    private $blackList;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=255)
     */
    private $reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="handleTime", type="datetime",nullable=true)
     */
    private $handleTime;

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
     * Set reason
     *
     * @param string $reason
     * @return Appeal
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Appeal
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return Appeal
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
     * Set blackList
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\BlackList $blackList
     * @return Appeal
     */
    public function setBlackList(\Auto\Bundle\ManagerBundle\Entity\BlackList $blackList = null)
    {
        $this->blackList = $blackList;

        return $this;
    }

    /**
     * Get blackList
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\BlackList
     */
    public function getBlackList()
    {
        return $this->blackList;
    }

    /**
     * Set handleTime
     *
     * @param \DateTime $handleTime
     * @return Appeal
     */
    public function setHandleTime($handleTime)
    {
        $this->handleTime = $handleTime;

        return $this;
    }

    /**
     * Get handleTime
     *
     * @return \DateTime 
     */
    public function getHandleTime()
    {
        return $this->handleTime;
    }
}
