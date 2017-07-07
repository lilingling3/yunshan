<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invite
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Invite
{

    const DEFAULT_CH = 1001; // 默认
    const WE_CHAT = 1002; // 微信
    const TENCENT = 1003; // QQ
    const WEIBO   = 1004; // 微博
    

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
    private $inviter;

    /**
     * @var string
     *
     * @ORM\Column(name="inviteeMobile", type="string", length=11,nullable=true)
     */
    private $inviteeMobile;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="channel", type="integer",options={"default"=0},nullable=true)
     */
    private $channel;

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
     * Set inviter
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $inviter
     * @return Invite
     */
    public function setInviter(\Auto\Bundle\ManagerBundle\Entity\Member $inviter = null)
    {
        $this->inviter = $inviter;

        return $this;
    }

    /**
     * Get inviter
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getInviter()
    {
        return $this->inviter;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return Invite
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
     * Set inviteeMobile
     *
     * @param string $inviteeMobile
     * @return Invite
     */
    public function setInviteeMobile($inviteeMobile)
    {
        $this->inviteeMobile = $inviteeMobile;

        return $this;
    }

    /**
     * Get inviteeMobile
     *
     * @return string 
     */
    public function getInviteeMobile()
    {
        return $this->inviteeMobile;
    }


    /**
     * Set channel
     *
     * @param integer $channel
     * @return Invite
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel
     *
     * @return integer 
     */
    public function getChannel()
    {
        return $this->channel;
    }



}
