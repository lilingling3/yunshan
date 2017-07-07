<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZhimaXinyong
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ZhimaXinyong
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
     * @var integer
     *
     * @ORM\Column(name="member_id", type="integer")
     */
    private $memberId;

    /**
     * @var string
     *
     * @ORM\Column(name="zm_score", type="string", length=20)
     */
    private $zmScore;

    /**
     * @var string
     *
     * @ORM\Column(name="level", type="string", length=20)
     */
    private $level;

    /**
     * @var string
     *
     * @ORM\Column(name="biz_no", type="string", length=255)
     */
    private $bizNo;


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
     * Set memberId
     *
     * @param integer $memberId
     * @return ZhimaXinyong
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;

        return $this;
    }

    /**
     * Get memberId
     *
     * @return integer
     */
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * Set zmScore
     *
     * @param string $zmScore
     * @return ZhimaXinyong
     */
    public function setZmScore($zmScore)
    {
        $this->zmScore = $zmScore;

        return $this;
    }

    /**
     * Get zmScore
     *
     * @return string
     */
    public function getZmScore()
    {
        return $this->zmScore;
    }

    /**
     * Set level
     *
     * @param string $level
     * @return ZhimaXinyong
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set bizNo
     *
     * @param string $bizNo
     * @return ZhimaXinyong
     */
    public function setBizNo($bizNo)
    {
        $this->bizNo = $bizNo;

        return $this;
    }

    /**
     * Get bizNo
     *
     * @return string
     */
    public function getBizNo()
    {
        return $this->bizNo;
    }
}
