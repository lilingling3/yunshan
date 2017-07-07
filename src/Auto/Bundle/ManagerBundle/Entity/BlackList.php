<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlackList
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class BlackList
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
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="AuthMember")
     */
    private $authMember;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endTime", type="datetime",nullable=true)
     */
    private $endTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="reason", type="smallint")
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="detail", type="string", length=255)
     */
    private $detail;



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
     * @return BlackList
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
     * @return BlackList
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
     * Set reason
     *
     * @param integer $reason
     * @return BlackList
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return integer 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set detail
     *
     * @param string $detail
     * @return BlackList
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get detail
     *
     * @return string 
     */
    public function getDetail()
    {
        return $this->detail;
    }


    /**
     * Set authMember
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\AuthMember $authMember
     * @return AuthMember
     */
    public function setAuthMember(\Auto\Bundle\ManagerBundle\Entity\AuthMember $authMember = null)
    {
        $this->authMember = $authMember;

        return $this;
    }

    /**
     * Get authMember
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\AuthMember
     */
    public function getAuthMember()
    {
        return $this->authMember;
    }
}
