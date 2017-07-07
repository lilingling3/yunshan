<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RentalCar
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RentalCar
{

    const RENTAL_ABLE = 300;  //可租赁
    const RENTAL_UNABLE = 301; //不可租赁(已被租赁)
    const SAME_PLACE_BACK = 600;//原地还车
    const DIFFERENT_PLACE_BACK = 601;//异地还车
    const RENTAL_CAR_ONLINE = 701;//上线
    const RENTAL_CAR_OFFLINE = 702;//下线

    //operationKind   1营运 2非营运 3租赁

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
     * @var Car
     *
     * @ORM\ManyToOne(targetEntity="Car")
     */
    private $car;

    /**
     * @var RentalStation
     *
     * @ORM\ManyToOne(targetEntity="RentalStation",inversedBy="rentalCars")
     */
    private $rentalStation;

    /**
     * @var string
     *
     * @ORM\Column(name="licensePlate", type="string", length=255)
     */
    private $licensePlate;

    /**
     * @var LicensePlace
     *
     * @ORM\ManyToOne(targetEntity="LicensePlace")
     */
    private $licensePlace;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $company;

    /**
     * @var deviceCompany
     *
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $deviceCompany;

    /**
     * @var string
     *
     * @ORM\Column(name="engineNumber", type="string", length=255)
     */
    private $engineNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="chassisNumber", type="string", length=255,nullable=true)
     */
    private $chassisNumber;



    /**
     * @var Online
     *
     * @ORM\ManyToOne(targetEntity="RentalCarOnlineRecord")
     */
    private $online;

    /**
     * @var Color
     *
     * @ORM\ManyToOne(targetEntity="Color")
     */

    private $color;

    /**
     * @var array
     *
     * @ORM\Column(name="images", type="json_array",nullable=true)
     */
    private $images;

    /**
     * @var string
     *
     * @ORM\Column(name="boxId", type="string",nullable=true)
     */
    private $boxId;

    /**
     * @var float
     *
     * @ORM\Column(name="buyPrice", type="float",nullable=true)
     */
    private $buyPrice;

    /**
     * @var \Date
     *
     * @ORM\Column(name="registerDate", type="date",nullable=true)
     */
    private $registerDate;


    /**
     * @var integer
     *
     * @ORM\Column(name="operationKind", type="integer",nullable=true)
     */
    private $operationKind;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime",nullable=true)
     */
    private $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="inspectTime", type="datetime",nullable=true)
     */
    private $inspectTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="insuranceType", type="smallint",nullable=true)
     */
    private $insuranceType;


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
     * Set licensePlate
     *
     * @param string $licensePlate
     * @return RentalCar
     */
    public function setLicensePlate($licensePlate)
    {
        $this->licensePlate = $licensePlate;

        return $this;
    }

    /**
     * Get licensePlate
     *
     * @return string 
     */
    public function getLicensePlate()
    {
        return $this->licensePlate;
    }

    /**
     * Set engineNumber
     *
     * @param string $engineNumber
     * @return RentalCar
     */
    public function setEngineNumber($engineNumber)
    {
        $this->engineNumber = $engineNumber;

        return $this;
    }

    /**
     * Get engineNumber
     *
     * @return string 
     */
    public function getEngineNumber()
    {
        return $this->engineNumber;
    }

    /**
     * Set car
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Car $car
     * @return RentalCar
     */
    public function setCar(\Auto\Bundle\ManagerBundle\Entity\Car $car = null)
    {
        $this->car = $car;

        return $this;
    }

    /**
     * Get car
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Car 
     */
    public function getCar()
    {
        return $this->car;
    }

    /**
     * Set color
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Color $color
     * @return RentalCar
     */
    public function setColor(\Auto\Bundle\ManagerBundle\Entity\Color $color = null)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Color 
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set licensePlace
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\LicensePlace $licensePlace
     * @return RentalCar
     */
    public function setLicensePlace(\Auto\Bundle\ManagerBundle\Entity\LicensePlace $licensePlace = null)
    {
        $this->licensePlace = $licensePlace;

        return $this;
    }

    /**
     * Get licensePlace
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\LicensePlace 
     */
    public function getLicensePlace()
    {
        return $this->licensePlace;
    }

    public function getLicense()
    {
        return sprintf('%s%s', $this->licensePlace->getName(), $this->licensePlate);
    }

    /**
     * Set rentalStation
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation
     * @return RentalCar
     */
    public function setRentalStation(\Auto\Bundle\ManagerBundle\Entity\RentalStation $rentalStation = null)
    {
        $this->rentalStation = $rentalStation;

        return $this;
    }

    /**
     * Get rentalStation
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalStation 
     */
    public function getRentalStation()
    {
        return $this->rentalStation;
    }

    /**
     * Set images
     *
     * @param array $images
     * @return RentalStation
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Get images
     *
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }


    /**
     * Set company
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Company $company
     * @return RentalCar
     */
    public function setCompany(\Auto\Bundle\ManagerBundle\Entity\Company $company = null)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Company 
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return RentalCar
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
     * Set chassisNumber
     *
     * @param string $chassisNumber
     * @return RentalCar
     */
    public function setChassisNumber($chassisNumber)
    {
        $this->chassisNumber = $chassisNumber;

        return $this;
    }

    /**
     * Get chassisNumber
     *
     * @return string 
     */
    public function getChassisNumber()
    {
        return $this->chassisNumber;
    }

    /**
     * Set online
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord $online
     * @return RentalCar
     */
    public function setOnline(\Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord $online = null)
    {
        $this->online = $online;

        return $this;
    }

    /**
     * Get online
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord 
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * Set inspectTime
     *
     * @param \DateTime $inspectTime
     * @return RentalCar
     */
    public function setInspectTime($inspectTime)
    {
        $this->inspectTime = $inspectTime;

        return $this;
    }

    /**
     * Get inspectTime
     *
     * @return \DateTime 
     */
    public function getInspectTime()
    {
        return $this->inspectTime;
    }

    /**
     * Set operationKind
     *
     * @param integer $operationKind
     * @return RentalCar
     */
    public function setOperationKind($operationKind)
    {
        $this->operationKind = $operationKind;

        return $this;
    }

    /**
     * Get operationKind
     *
     * @return integer 
     */
    public function getOperationKind()
    {
        return $this->operationKind;
    }

    /**
     * Set boxId
     *
     * @param string $boxId
     * @return RentalCar
     */
    public function setBoxId($boxId)
    {
        $this->boxId = $boxId;

        return $this;
    }

    /**
     * Get boxId
     *
     * @return string 
     */
    public function getBoxId()
    {
        return $this->boxId;
    }

    /**
     * Set deviceCompany
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Company $deviceCompany
     * @return RentalCar
     */
    public function setDeviceCompany(\Auto\Bundle\ManagerBundle\Entity\Company $deviceCompany = null)
    {
        $this->deviceCompany = $deviceCompany;

        return $this;
    }

    /**
     * Get deviceCompany
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Company 
     */
    public function getDeviceCompany()
    {
        return $this->deviceCompany;
    }

    /**
     * Set buyPrice
     *
     * @param float $buyPrice
     * @return RentalCar
     */
    public function setBuyPrice($buyPrice)
    {
        $this->buyPrice = $buyPrice;

        return $this;
    }

    /**
     * Get buyPrice
     *
     * @return float 
     */
    public function getBuyPrice()
    {
        return $this->buyPrice;
    }

    /**
     * Set registerDate
     *
     * @param \DateTime $registerDate
     * @return RentalCar
     */
    public function setRegisterDate($registerDate)
    {
        $this->registerDate = $registerDate;

        return $this;
    }

    /**
     * Get registerDate
     *
     * @return \DateTime 
     */
    public function getRegisterDate()
    {
        return $this->registerDate;
    }


    /**
     * Set insuranceType
     *
     * @param integer $insuranceType
     * @return RentalCar
     */
    public function setInsuranceType($insuranceType)
    {
        $this->insuranceType = $insuranceType;

        return $this;
    }

    /**
     * Get insuranceType
     *
     * @return integer 
     */
    public function getInsuranceType()
    {
        return $this->insuranceType;
    }
}
