<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CarProblem
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class CarProblem
{
    const ALREADY    = 101;//已处理
    const UNTREATED  = 102;//未处理
    const PENDING    = 103;//待处理
    const REPEAT     = 104;//重复
    const FALSE      = 105;//不实
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
     * @ORM\Column(name="ImageLabel", type="integer")
     */
    private $imageLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=100)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="carType", type="string", length=100)
     */
    private $carType;

    /**
     * @var string
     *
     * @ORM\Column(name="licensePlace", type="string", length=100)
     */
    private $licensePlace;

    /**
     * @var string
     *
     * @ORM\Column(name="plateNumber", type="string", length=100)
     */
    private $plateNumber;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="problem", type="string", length=100)
     */
    private $problem;

    /**
     * @var string
     *
     * @ORM\Column(name="coupon", type="string", length=100)
     */
    private $coupon;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100)
     */
    private $state;


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
     * Set imageLabel
     *
     * @param integer $imageLabel
     * @return QiniuImage
     */
    public function setImageLabel($imageLabel)
    {
        $this->imageLabel = $imageLabel;

        return $this;
    }

    /**
     * Get imageLabel
     *
     * @return integer
     */
    public function getImageLabel()
    {
        return $this->imageLabel;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     * @return CarProblem
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CarProblem
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
     * Set carType
     *
     * @param string $carType
     * @return CarProblem
     */
    public function setCarType($carType)
    {
        $this->carType = $carType;

        return $this;
    }

    /**
     * Get carType
     *
     * @return string
     */
    public function getCarType()
    {
        return $this->carType;
    }

    /**
     * Set licensePlace
     *
     * @param string $licensePlace
     * @return CarProblem
     */
    public function setLicensePlace($licensePlace)
    {
        $this->licensePlace = $licensePlace;

        return $this;
    }

    /**
     * Get licensePlace
     *
     * @return string
     */
    public function getLicensePlace()
    {
        return $this->licensePlace;
    }

    /**
     * Set plateNumber
     *
     * @param string $plateNumber
     * @return CarProblem
     */
    public function setPlateNumber($plateNumber)
    {
        $this->plateNumber = $plateNumber;

        return $this;
    }

    /**
     * Get plateNumber
     *
     * @return string
     */
    public function getPlateNumber()
    {
        return $this->plateNumber;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return CarProblem
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
     * Set problem
     *
     * @param string $problem
     * @return CarProblem
     */
    public function setProblem($problem)
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * Get problem
     *
     * @return string
     */
    public function getProblem()
    {
        return $this->problem;
    }

    /**
     * Set coupon
     *
     * @param string $coupon
     * @return CarProblem
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * Get coupon
     *
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return CarProblem
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
