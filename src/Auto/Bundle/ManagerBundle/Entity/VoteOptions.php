<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VoteOptions
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class VoteOptions
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255,nullable=true)
     */
    private $image;


    /**
     * @var Vote
     *
     * @ORM\ManyToOne(targetEntity="Vote")
     */
    private $vote;


    /**
     * @ORM\OneToMany(targetEntity="VoteRecords", mappedBy="option")
     * @ORM\OrderBy({"id"="ASC"})
     */
    private $records;



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
     * @return VoteOptions
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
     * Set image
     *
     * @param string $image
     * @return VoteOptions
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set vote
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Vote $vote
     * @return VoteOptions
     */
    public function setVote(\Auto\Bundle\ManagerBundle\Entity\Vote $vote = null)
    {
        $this->vote = $vote;

        return $this;
    }

    /**
     * Get vote
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Vote 
     */
    public function getVote()
    {
        return $this->vote;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->records = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add records
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\VoteRecords $records
     * @return VoteOptions
     */
    public function addRecord(\Auto\Bundle\ManagerBundle\Entity\VoteRecords $records)
    {
        $this->records[] = $records;

        return $this;
    }

    /**
     * Remove records
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\VoteRecords $records
     */
    public function removeRecord(\Auto\Bundle\ManagerBundle\Entity\VoteRecords $records)
    {
        $this->records->removeElement($records);
    }

    /**
     * Get records
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRecords()
    {
        return $this->records;
    }
}
