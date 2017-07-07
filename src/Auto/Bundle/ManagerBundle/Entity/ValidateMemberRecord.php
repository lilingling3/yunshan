<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthMember
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ValidateMemberRecord
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
     * @ORM\OneToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="resultJson", type="text")
     */
    private $resultJson;//接口返回结果

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
     * @return ValidateMemberRecord
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
     * Set resultJson
     *
     * @param string $resultJson
     * @return ValidateMemberRecord
     */
    public function setResultJson($resultJson)
    {
        $this->resultJson = $resultJson;

        return $this;
    }

    /**
     * Get resultJson
     *
     * @return string 
     */
    public function getResultJson()
    {
        return $this->resultJson;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return ValidateMemberRecord
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
