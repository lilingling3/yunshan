<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InsuranceRecord
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RentalCarInsuranceRecord
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
     * @var string
     *
     * @ORM\Column(name="request", type="text")
     */
    private $request;

    /**
     * @var string
     *
     * @ORM\Column(name="response", type="text")
     */
    private $response;

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
     * Set request
     *
     * @param string $insurance
     * @return RentalCarInsuranceRecord
     */
    public function setRequestLog($insurance)
    {
        $this->request = $insurance;

        return $this;
    }

    /**
     * Get request
     *
     * @return string 
     */
    public function getRequestLog()
    {
        return $this->request;
    }


    /**
     * Set response
     *
     * @param string $insurance
     * @return RentalCarInsuranceRecord
     */
    public function setResponseLog($insurance)
    {
        $this->response = $insurance;

        return $this;
    }

    /**
     * Get response
     *
     * @return string 
     */
    public function getResponseLog()
    {
        return $this->response;
    }



    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return InsuranceRecord
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
