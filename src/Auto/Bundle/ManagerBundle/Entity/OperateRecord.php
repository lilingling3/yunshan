<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperateRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OperateRecord
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
    private $operateMember;

    /**
     * @var integer
     *
     * @ORM\Column(name="behavior", type="smallint")
     */
    private $behavior;

    /**
     * @var integer
     *
     * @ORM\Column(name="objectId", type="integer",nullable=true)
     */
    private $objectId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="objectName", type="string", length=255)
     */
    private $objectName;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255)
     */
    private $content;

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
     * Set behavior
     *
     * @param integer $behavior
     * @return OperateRecord
     */
    public function setBehavior($behavior)
    {
        $this->behavior = $behavior;

        return $this;
    }

    /**
     * Get behavior
     *
     * @return integer 
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * Set objectId
     *
     * @param integer $objectId
     * @return OperateRecord
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * Get objectId
     *
     * @return integer 
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return OperateRecord
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
     * Set objectName
     *
     * @param string $objectName
     * @return OperateRecord
     */
    public function setObjectName($objectName)
    {
        $this->objectName = $objectName;

        return $this;
    }

    /**
     * Get objectName
     *
     * @return string 
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return OperateRecord
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * Set operateMember
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $operateMember
     * @return OperateRecord
     */
    public function setOperateMember(\Auto\Bundle\ManagerBundle\Entity\Member $operateMember = null)
    {
        $this->operateMember = $operateMember;

        return $this;
    }

    /**
     * Get operateMember
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Member 
     */
    public function getOperateMember()
    {
        return $this->operateMember;
    }
}
