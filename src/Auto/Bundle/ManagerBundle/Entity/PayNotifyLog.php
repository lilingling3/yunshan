<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PayNotifyLog
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class PayNotifyLog
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
     * @var integer
     *
     * @ORM\Column(name="type", type="integer",options={"comment":"1:微信返回，2：支付宝返回,3:银联返回"})
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createTime", type="datetime")
     */
    private $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="jsonContent", type="text",options={"comment":"接口返回的json数据"})
     */
    private $jsonContent;



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
     * Set type
     *
     * @param integer $type
     * @return PayNotifyLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return PayNotifyLog
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
     * Set jsonContent
     *
     * @param string $jsonContent
     * @return PayNotifyLog
     */
    public function setJsonContent($jsonContent)
    {
        $this->jsonContent = $jsonContent;

        return $this;
    }

    /**
     * Get jsonContent
     *
     * @return string 
     */
    public function getJsonContent()
    {
        return $this->jsonContent;
    }
}
