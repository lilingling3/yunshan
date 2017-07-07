<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthMember
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class AuthMember
{
    const NO_UPDATE_AUTH = 200;// 未认证
    const UPDATED_NO_AUTH = 201;// 上传认证信息未认证
    const AUTH_FAILED = 202;// 认证失败
    const AUTH_LICENSE_EXPIRE = 203;// 认证过期
    const AUTH_SUCCESS = 299;// 认证成功

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
     * @ORM\Column(name="licenseImage", type="text")
     */
    private $licenseImage;//驾驶证照片

    /**
     * @var string
     *
     * @ORM\Column(name="licenseNumber", type="text",nullable=true)
     */
    private $licenseNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="licenseAddress", type="text",nullable=true)
     */
    private $licenseAddress;

    /**
     * @var integer
     *
     * @ORM\Column(name="licenseImageAuthError", type="integer",nullable=true)
     */
    private $licenseImageAuthError;//驾驶证审核结果

    /**
     * @var string
     *
     * @ORM\Column(name="idImage", type="text",nullable=true)
     */
    private $idImage;//身份证照片

    /**
     * @var integer
     *
     * @ORM\Column(name="idImageAuthError", type="integer",nullable=true)
     */
    private $idImageAuthError;//身份证审核结果

    /**
     * @var string
     *
     * @ORM\Column(name="idHandImage", type="text",nullable=true)
     */
    private $idHandImage;//手持身份证照片

    /**
     * @var integer
     *
     * @ORM\Column(name="idHandImageAuthError", type="integer",nullable=true)
     */
    private $idHandImageAuthError;//手持身份证审核结果

    /**
     * @var string
     *
     * @ORM\Column(name="IDNumber", type="string", length=255,nullable=true)
     */
    private $IDNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="IDAddress", type="string", length=255,nullable=true)
     */
    private $IDAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="licenseProvince", type="string", length=255,nullable=true)
     */
    private $licenseProvince;

    /**
     * @var string
     *
     * @ORM\Column(name="licenseCity", type="string", length=255,nullable=true)
     */
    private $licenseCity;

    /**
     * @var string
     *
     * @ORM\Column(name="documentNumber", type="string", length=255,nullable=true)
     */
    private $documentNumber;


    /**
     * @var integer
     *
     * @ORM\Column(name="licenseAuthError", type="integer",options={"default"=0},nullable=true)
     */
    private $licenseAuthError;

    /**
     * @var integer
     *
     * @ORM\Column(name="licenseValidYear", type="integer",options={"default"=0},nullable=true)
     */
    private $licenseValidYear;


    /**
     * @var integer
     *
     * @ORM\Column(name="mobileCallError", type="integer",nullable=true)
     */
    private $mobileCallError;//电话回访审核

    /**
     * @var string
     *
     * @ORM\Column(name="validateResult", type="string", length=255,nullable=true)
     */
    private $validateResult;

    /**
     * @var integer
     *
     * @ORM\Column(name="validateError", type="integer",nullable=true)
     */
    private $validateError;
    

    /**
     * @var string
     *
     * @ORM\Column(name="validateNewResult", type="string", length=255,nullable=true)
     */
    private $validateNewResult;

    /**
     * @var integer
     *
     * @ORM\Column(name="submitType", type="integer",nullable=true)
     */
    private $submitType;


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
     * @var \DateTime
     *
     * @ORM\Column(name="authTime", type="datetime",nullable=true)
     */
    private $authTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="applyTime", type="datetime",nullable=true)
     */
    private $applyTime;

    /**
     * @var \Date
     *
     * @ORM\Column(name="licenseStartDate", type="date",nullable=true)
     */
    private $licenseStartDate;

    /**
     * @var \Date
     *
     * @ORM\Column(name="licenseEndDate", type="date",nullable=true)
     */
    private $licenseEndDate;


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

     * Set licenseAddress
    *
    * @param string $licenseImage
    * @return AuthMember
    */
    public function setLicenseAddress($licenseAddress)
    {
        $this->licenseAddress = $licenseAddress;

        return $this;
    }

    /**
     * Get licenseAddress
     *
     * @return string
     */
    public function getLicenseAddress()
    {
        return $this->licenseAddress;
    }



    /**
     * Set IDAddress
     *
     * @param string $iDAddress
     * @return AuthMember
     */
    public function setIDAddress($iDAddress)
    {
        $this->IDAddress = $iDAddress;

        return $this;
    }

    /**
     * Get IDAddress
     *
     * @return string
     */
    public function getIDAddress()
    {
        return $this->IDAddress;
    }

    /**
     * Set documentNumber
     *
     * @param string $documentNumber
     * @return AuthMember
     */
    public function setDocumentNumber($documentNumber)
    {
        $this->documentNumber = $documentNumber;

        return $this;
    }

    /**
     * Get documentNumber
     *
     * @return string
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    /**
     * Set licenseAuthError
     *
     * @param integer $licenseAuthError
     * @return AuthMember
     */
    public function setLicenseAuthError($licenseAuthError)
    {
        $this->licenseAuthError = $licenseAuthError;

        return $this;
    }

    /**
     * Get licenseAuthError
     *
     * @return integer
     */
    public function getLicenseAuthError()
    {
        return $this->licenseAuthError;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return AuthMember
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
     * Set authTime
     *
     * @param \DateTime $authTime
     * @return AuthMember
     */
    public function setAuthTime($authTime)
    {
        $this->authTime = $authTime;

        return $this;
    }

    /**
     * Get authTime
     *
     * @return \DateTime
     */
    public function getAuthTime()
    {
        return $this->authTime;
    }

    /**
     * Set applyTime
     *
     * @param \DateTime $applyTime
     * @return AuthMember
     */
    public function setApplyTime($applyTime)
    {
        $this->applyTime = $applyTime;

        return $this;
    }

    /**
     * Get applyTime
     *
     * @return \DateTime
     */
    public function getApplyTime()
    {
        return $this->applyTime;
    }

    /**
     * Set licenseStartDate
     *
     * @param \DateTime $licenseStartDate
     * @return AuthMember
     */
    public function setLicenseStartDate($licenseStartDate)
    {
        $this->licenseStartDate = $licenseStartDate;

        return $this;
    }

    /**
     * Get licenseStartDate
     *
     * @return \DateTime
     */
    public function getLicenseStartDate()
    {
        return $this->licenseStartDate;
    }

    /**
     * Set licenseEndDate
     *
     * @param \DateTime $licenseEndDate
     * @return AuthMember
     */
    public function setLicenseEndDate($licenseEndDate)
    {
        $this->licenseEndDate = $licenseEndDate;

        return $this;
    }

    /**
     * Get licenseEndDate
     *
     * @return \DateTime
     */
    public function getLicenseEndDate()
    {
        return $this->licenseEndDate;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return AuthMember
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
     * Get status
     *
     * @return integer
     */
    public function getStatusNew()
    {
        if($this->getAuthTime()) {
            if ($this->getAuthTime()->getTimestamp() > (new \DateTime('2017-02-18 14:00:00'))->getTimestamp()) {


                if (($this->getValidateError() == 0) && ($this->getLicenseImageAuthError() == 0) &&
                    ($this->getIdImageAuthError() == 0) && ($this->getIdHandImageAuthError() == 0)
                ) {

                    if (($this->getSubmitType()) === null) {
                        $status = $this::UPDATED_NO_AUTH;
                    } else if (($this->getSubmitType() === 0)) {

                        if ($this->getLicenseEndDate() < (new \DateTime())) {
                            $status = $this::AUTH_LICENSE_EXPIRE;
                        } else {
                            $status = $this::AUTH_SUCCESS;
                        }
                    } else {
                        $status = $this::AUTH_FAILED;
                    }

                } else {
                    $status = $this::AUTH_FAILED;
                }
            }
             else  {

                if (($this->getValidateError() == 0) && ($this->getLicenseImageAuthError() == 0) && ($this->getIdImageAuthError() == 0) && ($this->getIdHandImageAuthError() == 0) && ($this->getMobileCallError() == 0)) {
                    if ($this->getLicenseEndDate() < (new \DateTime())) {
                        $status = $this::AUTH_LICENSE_EXPIRE;
                    } else {
                        $status = $this::AUTH_SUCCESS;
                    }
                } else {
                    $status = $this::AUTH_FAILED;
                }
            }
        }else{
            $status= $this::UPDATED_NO_AUTH;
        }
        

        return $status;
    }

    public function getStatus()
    {
        if($this->getAuthTime()){
            if($this->getLicenseAuthError()==0){
                if($this->getLicenseEndDate()<(new \DateTime())) {
                    $status = $this::AUTH_LICENSE_EXPIRE;
                }else{
                    $status = $this::AUTH_SUCCESS;
                }
            }else{
                $status = $this::AUTH_FAILED;
            }
        }else{
            $status= $this::UPDATED_NO_AUTH;
        }

        return $status;
    }

    /**
     * Set idImage
     *
     * @param string $idImage
     * @return AuthMember
     */
    public function setIdImage($idImage)
    {
        $this->idImage = $idImage;

        return $this;
    }

    /**
     * Get idImage
     *
     * @return string
     */
    public function getIdImage()
    {
        return $this->idImage;
    }

    /**
     * Set licenseImageAuthError
     *
     * @param integer $licenseImageAuthError
     * @return AuthMember
     */
    public function setLicenseImageAuthError($licenseImageAuthError)
    {
        $this->licenseImageAuthError = $licenseImageAuthError;

        return $this;
    }

    /**
     * Get licenseImageAuthError
     *
     * @return integer
     */
    public function getLicenseImageAuthError()
    {
        return $this->licenseImageAuthError;
    }

    /**
     * Set idImageAuthError
     *
     * @param integer $idImageAuthError
     * @return AuthMember
     */
    public function setIdImageAuthError($idImageAuthError)
    {
        $this->idImageAuthError = $idImageAuthError;

        return $this;
    }

    /**
     * Get idImageAuthError
     *
     * @return integer
     */
    public function getIdImageAuthError()
    {
        return $this->idImageAuthError;
    }

    /**
     * Set idHandImage
     *
     * @param string $idHandImage
     * @return AuthMember
     */
    public function setIdHandImage($idHandImage)
    {
        $this->idHandImage = $idHandImage;

        return $this;
    }

    /**
     * Get idHandImage
     *
     * @return string
     */
    public function getIdHandImage()
    {
        return $this->idHandImage;
    }

    /**
     * Set idHandImageAuthError
     *
     * @param integer $idHandImageAuthError
     * @return AuthMember
     */
    public function setIdHandImageAuthError($idHandImageAuthError)
    {
        $this->idHandImageAuthError = $idHandImageAuthError;

        return $this;
    }

    /**
     * Get idHandImageAuthError
     *
     * @return integer
     */
    public function getIdHandImageAuthError()
    {
        return $this->idHandImageAuthError;
    }

    /**
     * Set mobileCallError
     *
     * @param integer $mobileCallError
     * @return AuthMember
     */
    public function setMobileCallError($mobileCallError)
    {
        $this->mobileCallError = $mobileCallError;

        return $this;
    }

    /**
     * Get mobileCallError
     *
     * @return integer
     */
    public function getMobileCallError()
    {
        return $this->mobileCallError;
    }

    /**
     * Set validateResult
     *
     * @param string $validateResult
     * @return AuthMember
     */
    public function setValidateResult($validateResult)
    {
        $this->validateResult = $validateResult;

        return $this;
    }

    /**
     * Get validateResult
     *
     * @return string
     */
    public function getValidateResult()
    {
        return $this->validateResult;
    }
    /**
     * Set validateNewResult
     *
     * @param string $validateNewResult
     * @return AuthMember
     */
    public function setValidateNewResult($validateNewResult)
    {
        $this->validateNewResult = $validateNewResult;

        return $this;
    }

    /**
     * Get validateNewResult
     *
     * @return string
     */
    public function getValidateNewResult()
    {
        return $this->validateNewResult;
    }
    /**
     * Set validateError
     *
     * @param integer $validateError
     * @return AuthMember
     */
    public function setValidateError($validateError)
    {
        $this->validateError = $validateError;

        return $this;
    }

    /**
     * Get validateError
     *
     * @return integer
     */
    public function getValidateError()
    {
        return $this->validateError;
    }



    /**
     * Set submitType
     *
     * @param integer $submitType
     * @return AuthMember
     */
    public function setSubmitType($submitType)
    {
        $this->submitType = $submitType;

        return $this;
    }

    /**
     * Get submitType
     *
     * @return integer
     */
    public function getSubmitType()
    {
        return $this->submitType;
    }

    /**
     * Set licenseImage
     *
     * @param string $licenseImage
     * @return AuthMember
     */
    public function setLicenseImage($licenseImage)
    {
        $this->licenseImage = $licenseImage;

        return $this;
    }

    /**
     * Get licenseImage
     *
     * @return string 
     */
    public function getLicenseImage()
    {
        return $this->licenseImage;
    }


    /**
     * Set IDNumber
     *
     * @param string $iDNumber
     * @return AuthMember
     */
    public function setIDNumber($iDNumber)
    {
        $this->IDNumber = $iDNumber;

        return $this;
    }

    /**
     * Get IDNumber
     *
     * @return string 
     */
    public function getIDNumber()
    {
        return $this->IDNumber;
    }

    /**
     * Set licenseNumber
     *
     * @param string $licenseNumber
     * @return AuthMember
     */
    public function setLicenseNumber($licenseNumber)
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    /**
     * Get licenseNumber
     *
     * @return string 
     */
    public function getLicenseNumber()
    {
        return $this->licenseNumber;
    }

    /**
     * Set licenseValidYear
     *
     * @param integer $licenseValidYear
     * @return AuthMember
     */
    public function setLicenseValidYear($licenseValidYear)
    {
        $this->licenseValidYear = $licenseValidYear;

        return $this;
    }

    /**
     * Get licenseValidYear
     *
     * @return integer 
     */
    public function getLicenseValidYear()
    {
        return $this->licenseValidYear;
    }

    /**
     * Set licenseProvince
     *
     * @param string $licenseProvince
     * @return AuthMember
     */
    public function setLicenseProvince($licenseProvince)
    {
        $this->licenseProvince = $licenseProvince;

        return $this;
    }

    /**
     * Get licenseProvince
     *
     * @return string 
     */
    public function getLicenseProvince()
    {
        return $this->licenseProvince;
    }

    /**
     * Set licenseCity
     *
     * @param string $licenseCity
     * @return AuthMember
     */
    public function setLicenseCity($licenseCity)
    {
        $this->licenseCity = $licenseCity;

        return $this;
    }

    /**
     * Get licenseCity
     *
     * @return string 
     */
    public function getLicenseCity()
    {
        return $this->licenseCity;
    }
}
