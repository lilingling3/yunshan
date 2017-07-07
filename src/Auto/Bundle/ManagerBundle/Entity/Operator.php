<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints;

/**
 * Operator
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Operator
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
     * @ORM\Column(name="position", type="smallint",nullable=true)
     */
    private $position;

    /**
     * @var Member
     *
     * @ORM\OneToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @ORM\ManyToMany(targetEntity="Station")
     * @ORM\OrderBy({"id"="ASC"})
     */
    private $stations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->stations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set position
     *
     * @param integer $position
     * @return Operator
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set member
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Member $member
     * @return Operator
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
     * Add stations
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Station $stations
     * @return Operator
     */
    public function addStation(\Auto\Bundle\ManagerBundle\Entity\Station $stations)
    {
        $this->stations[] = $stations;

        return $this;
    }

    /**
     * Remove stations
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Station $stations
     */
    public function removeStation(\Auto\Bundle\ManagerBundle\Entity\Station $stations)
    {
        $this->stations->removeElement($stations);
    }

    /**
     * Get stations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getStations()
    {
        return $this->stations;
    }
}
