<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QiniuImage
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class QiniuImage
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
     * @ORM\Column(name="ImageLabel", type="integer")
     */
    private $imageLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="Bucket", type="string", length=50)
     */
    private $bucket;

    /**
     * @var string
     *
     * @ORM\Column(name="Filename", type="string", length=255)
     */
    private $filename;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CreateTime", type="datetime")
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
     * Set bucket
     *
     * @param string $bucket
     * @return QiniuImage
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * Get bucket
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return QiniuImage
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return QiniuImage
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