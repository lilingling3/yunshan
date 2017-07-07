<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Company
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Company
{
    const COMPANY_KIND_CAR_BOX = 1;  //车载设备公司
    const COMPANY_KIND_INSURANCE = 2;  //保险公司
    const COMPANY_KIND_AUTO = 3;  //车辆归属公司
    const COMPANY_KIND_DELIVERY = 4;  //快递公司
    const COMPANY_KIND_REPAIR = 5;  //修理厂
    const COMPANY_KIND_RENTAL = 6;  //租赁公司

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
     * @var string
     *
     * @ORM\Column(name="fullName", type="string", length=255,nullable=true)
     */
    private $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="englishName", type="string", length=255,nullable=true)
     */
    private $englishName;

    /**
     * @var string
     *
     * @ORM\Column(name="caseNo", type="string", length=255,nullable=true)
     */
    private $caseNo;//备案号

    /**
     * @var string
     *
     * @ORM\Column(name="enterpriseCode", type="string", length=255,nullable=true)
     */
    private $enterpriseCode;//企业代码

    /**
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="Area")
     */
    private $area;


    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255,nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="contactName", type="string", length=255,nullable=true)
     */
    private $contactName;

    /**
     * @var string
     *
     * @ORM\Column(name="contactMobile", type="string", length=255,nullable=true)
     */
    private $contactMobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="kind", type="integer",nullable=true)
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
     * Set name
     *
     * @param string $name
     * @return Company
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
     * Set fullName
     *
     * @param string $fullName
     * @return Company
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get fullName
     *
     * @return string 
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set englishName
     *
     * @param string $englishName
     * @return Company
     */
    public function setEnglishName($englishName)
    {
        $this->englishName = $englishName;

        return $this;
    }

    /**
     * Get englishName
     *
     * @return string 
     */
    public function getEnglishName()
    {
        return $this->englishName;
    }

    /**
     * Set caseNo
     *
     * @param string $caseNo
     * @return Company
     */
    public function setCaseNo($caseNo)
    {
        $this->caseNo = $caseNo;

        return $this;
    }

    /**
     * Get caseNo
     *
     * @return string 
     */
    public function getCaseNo()
    {
        return $this->caseNo;
    }

    /**
     * Set enterpriseCode
     *
     * @param string $enterpriseCode
     * @return Company
     */
    public function setEnterpriseCode($enterpriseCode)
    {
        $this->enterpriseCode = $enterpriseCode;

        return $this;
    }

    /**
     * Get enterpriseCode
     *
     * @return string 
     */
    public function getEnterpriseCode()
    {
        return $this->enterpriseCode;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Company
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set contactName
     *
     * @param string $contactName
     * @return Company
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;

        return $this;
    }

    /**
     * Get contactName
     *
     * @return string 
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * Set contactMobile
     *
     * @param string $contactMobile
     * @return Company
     */
    public function setContactMobile($contactMobile)
    {
        $this->contactMobile = $contactMobile;

        return $this;
    }

    /**
     * Get contactMobile
     *
     * @return string 
     */
    public function getContactMobile()
    {
        return $this->contactMobile;
    }

    /**
     * Set kind
     *
     * @param integer $kind
     * @return Company
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

    /**
     * Set area
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Area $area
     * @return Company
     */
    public function setArea(\Auto\Bundle\ManagerBundle\Entity\Area $area = null)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Area 
     */
    public function getArea()
    {
        return $this->area;
    }
}
