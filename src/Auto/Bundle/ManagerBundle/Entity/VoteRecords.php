<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VoteRecords
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class VoteRecords
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
     * @var string
     *
     * @ORM\Column(name="wechatId", type="string", length=255)
     */

    private $wechatId;

    /**
     * @var VoteOptions
     *
     * @ORM\ManyToOne(targetEntity="VoteOptions")
     */
    private $option;

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
     * Set wechatId
     *
     * @param integer $wechatId
     * @return VoteRecords
     */
    public function setWechatId($wechatId)
    {
        $this->wechatId = $wechatId;

        return $this;
    }

    /**
     * Get wechatId
     *
     * @return integer 
     */
    public function getWechatId()
    {
        return $this->wechatId;
    }

    /**
     * Set option
     *
     * @param string $option
     * @return VoteRecords
     */
    public function setOption($option)
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Get option
     *
     * @return string 
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return VoteRecords
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
}
