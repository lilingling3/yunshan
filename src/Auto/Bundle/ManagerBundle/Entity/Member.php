<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Member
 *
 * @ORM\Table(
 *	uniqueConstraints={
 * 		@ORM\UniqueConstraint(columns={"mobile"})
 * 	}
 * )
 * @ORM\Entity
 * @UniqueEntity("mobile")
 * @UniqueEntity(fields="mobile", message="Mobile already taken")
 *
 */


class Member implements UserInterface, \Serializable
{
    const ROLE_USER     =  "ROLE_USER";        //普通注册用户
    const ROLE_ADMIN    =  "ROLE_ADMIN";      //管理员
    const ROLE_COO      =   "ROLE_COO";         //运营总监
    const ROLE_REGION_MANAGER      =   "ROLE_REGION_MANAGER";         //大区经理
    const ROLE_OPERATE  =  "ROLE_OPERATE";    //运营专员
    const ROLE_SERVER   =  "ROLE_SERVER";      //客服主管
    const ROLE_FINANCE  =  "ROLE_FINANCE";     //财务主管
    const ROLE_MARKET   =  "ROLE_MARKET";      //市场主管
    const MEMBER_SEX_MALE = 901;
    const MEMBER_SEX_FEMALE = 902;
    const MEMBER_SEX_UNKNOWN = 900;

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
     * @ORM\Column(name="username", type="string", length=255,nullable=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255,nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="nickname", type="string", length=255,nullable=true)
     */
    private $nickname;

    /**
     * @var string
     *
     * @ORM\Column(name="portrait", type="text",nullable=true)
     */
    private $portrait;



    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=60,nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=60,nullable=true)
     */
    private $salt;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=11)
     */
    private $mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="sex", type="integer",nullable=true)
     */
    private $sex;

    /**
     * @var string
     *
     * @ORM\Column(name="nation", type="string", length=50,nullable=true)
     */
    private $nation;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255,nullable=true)
     */
    private $address;

    /**
     * @var integer
     *
     * @ORM\Column(name="age", type="integer",nullable=true)
     */
    private $age;

    /**
     * @var integer
     *
     * @ORM\Column(name="business", type="integer",nullable=true)
     */
    private $business;

    /**
     * @var string
     *
     * @ORM\Column(name="wechatId", type="string",nullable=true)
     */
    private $wechatId;

    /**
     * @var string
     *
     * @ORM\Column(name="letvId", type="string",nullable=true)
     */
    private $letvId;

    /**
     * @var integer
     *
     * @ORM\Column(name="job", type="integer",nullable=true)
     */
    private $job;

    /**
     * @var wallet
     *
     * @ORM\Column(name="wallet", type="float",nullable=true,options={"default"=0})
     */
    private $wallet;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastLoginTime", type="datetime",nullable=true)
     */
    private $lastLoginTime;


    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="json_array", nullable=true)
     */
    private $roles;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255,nullable=true)
     */
    private $token;

    /**
     * @var integer
     *
     * @ORM\Column(name="source", type="integer",nullable=true)
     */
    private $source;/*新用户注册来源 3:h5普通 ,4:中央民族活动，5：北京外国语，6：中国人民，7：   ，8：望京SOHO，9：硅谷亮城，10：三里屯*/

    /**
     * @var string
     *
     * @ORM\Column(name="IdNumber", type="string", length=255,nullable=true)
     */
    private $IdNumber;//身份证号

    public function __construct()
    {

        $this->createTime = new \DateTime();
        // may not be needed, see section on salt below
        $this->salt = md5(uniqid(null, true));
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
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        $username = $this->username?$this->username:$this->mobile;
        return $username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Member
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set mobile
     *
     * @param integer $mobile
     * @return Member
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return integer
     */
    public function getMobile()
    {
        return $this->mobile;
    }


    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return Member
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
     * Set roles
     *
     * @param array $roles
     * @return Member
     */
    public function setRoles($roles)
    {
        $this->roles = $roles ? array_values($roles) : null;

        return $this;
    }

    /**
     * Get landscapes
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return $this->salt;
    }

    public function eraseCredentials()
    {
        // nothing.
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->mobile,
            $this->password,
            $this->salt,
        ));
    }

    public function unserialize($serialized)
    {

        list (
            $this->id,
            $this->mobile,
            $this->password,
            // see section on salt below
            $this->salt
            ) = unserialize($serialized);
    }


    /**
     * Set nickname
     *
     * @param string $nickname
     * @return Member
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Set portrait
     *
     * @param string $portrait
     * @return Member
     */
    public function setPortrait($portrait)
    {
        $this->portrait = $portrait;

        return $this;
    }

    /**
     * Get portrait
     *
     * @return string
     */
    public function getPortrait()
    {
        return $this->portrait;
    }

    /**
     * Set IDImages
     *
     * @param array $iDImages
     * @return Member
     */
    public function setIDImages($iDImages)
    {
        $this->IDImages = $iDImages;

        return $this;
    }

    /**
     * Get IDImages
     *
     * @return array
     */
    public function getIDImages()
    {
        return $this->IDImages;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return Member
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Set sex
     *
     * @param integer $sex
     * @return Member
     */
    public function setSex($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Get sex
     *
     * @return integer
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set age
     *
     * @param integer $age
     * @return Member
     */
    public function setAge($age)
    {
        $this->age = $age;

        return $this;
    }

    /**
     * Get age
     *
     * @return integer
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Set business
     *
     * @param integer $business
     * @return Member
     */
    public function setBusiness($business)
    {
        $this->business = $business;

        return $this;
    }

    /**
     * Get business
     *
     * @return integer
     */
    public function getBusiness()
    {
        return $this->business;
    }

    /**
     * Set job
     *
     * @param integer $job
     * @return Member
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Get job
     *
     * @return integer
     */
    public function getJob()
    {
        return $this->job;
    }



    /**
     * Set username
     *
     * @param string $username
     * @return Member
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Member
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
     * Set wechatId
     *
     * @param string $wechatId
     * @return Member
     */
    public function setWechatId($wechatId)
    {
        $this->wechatId = $wechatId;

        return $this;
    }

    /**
     * Get wechatId
     *
     * @return string
     */
    public function getWechatId()
    {
        return $this->wechatId;
    }

    /**
     * Set letvId
     *
     * @param string $letvId
     * @return Member
     */
    public function setLetvId($letvId)
    {
        $this->letvId = $letvId;

        return $this;
    }

    /**
     * Get letvId
     *
     * @return string
     */
    public function getLetvId()
    {
        return $this->letvId;
    }

    /**
     * Set wallet
     *
     * @param float $wallet
     * @return Member
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;

        return $this;
    }

    /**
     * Get wallet
     *
     * @return float
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Member
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set lastLoginTime
     *
     * @param \DateTime $lastLoginTime
     * @return Member
     */
    public function setLastLoginTime($lastLoginTime)
    {
        $this->lastLoginTime = $lastLoginTime;

        return $this;
    }

    /**
     * Get lastLoginTime
     *
     * @return \DateTime
     */
    public function getLastLoginTime()
    {
        return $this->lastLoginTime;
    }

    /**
     * Set source
     *
     * @param integer $source
     * @return Member
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return integer
     */
    public function getSource()
    {
        return $this->source;
    }




    /**
     * Set nation
     *
     * @param string $nation
     * @return Member
     */
    public function setNation($nation)
    {
        $this->nation = $nation;

        return $this;
    }

    /**
     * Get nation
     *
     * @return string 
     */
    public function getNation()
    {
        return $this->nation;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Member
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
     * Set IdNumber
     *
     * @param string $idNumber
     * @return Member
     */
    public function setIdNumber($idNumber)
    {
        $this->IdNumber = $idNumber;

        return $this;
    }

    /**
     * Get IdNumber
     *
     * @return string 
     */
    public function getIdNumber()
    {
        return $this->IdNumber;
    }
}
