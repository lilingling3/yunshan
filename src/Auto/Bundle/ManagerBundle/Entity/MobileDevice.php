<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MobileDevice
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class MobileDevice
{
    const MOBILE_DEVICE_KIND_LOGIN = 1;
    const MOBILE_DEVICE_KIND_OPEN = 2;

    const MOBILE_PLATFORM_IOS = 1;
    const MOBILE_PLATFORM_ANDROID = 2;
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
     * @ORM\Column(name="deviceToken", type="string", length=255)
     */
    private $deviceToken;

    /**
     * @var integer
     *
     * @ORM\Column(name="platform", type="smallint")
     */
    private $platform;

    /**
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     */

    private $member;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="kind", type="smallint")
     */
    private $kind;

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
     * Set deviceToken
     *
     * @param string $deviceToken
     * @return MobileDevice
     */
    public function setDeviceToken($deviceToken)
    {
        $this->deviceToken = $deviceToken;

        return $this;
    }

    /**
     * Get deviceToken
     *
     * @return string 
     */
    public function getDeviceToken()
    {
        return $this->deviceToken;
    }

    /**
     * Set platform
     *
     * @param integer $platform
     * @return MobileDevice
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * Get platform
     *
     * @return integer 
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return MobileDevice
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
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return MobileDevice
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
     * Set kind
     *
     * @param integer $kind
     * @return MobileDevice
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind
     *
     * @return integer 
     */
    public function getKind()
    {
        return $this->kind;
    }
}
