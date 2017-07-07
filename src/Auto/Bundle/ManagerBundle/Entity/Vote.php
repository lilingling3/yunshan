<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Vote
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Vote
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
     * @var integer
     *
     * @ORM\Column(name="countPrePerson", type="integer")
     */
    private $countPrePerson;

    /**
     * @var integer
     *
     * @ORM\Column(name="countPreDay", type="integer")
     */
    private $countPreDay;

    /**
     * @var integer
     *
     * @ORM\Column(name="countPreOptionPerson", type="integer")
     */
    private $countPreOptionPerson;

    /**
     * @var integer
     *
     * @ORM\Column(name="countPreOptionDay", type="integer")
     */
    private $countPreOptionDay;

    /**
     * @var \Date
     *
     * @ORM\Column(name="startDate", type="date",nullable=true)
     */

    private $startDate;

    /**
     * @var \Date
     *
     * @ORM\Column(name="endDate", type="date",nullable=true)
     */

    private $endDate;
    
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
     * @return Vote
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
     * Set countPrePerson
     *
     * @param integer $countPrePerson
     * @return Vote
     */
    public function setCountPrePerson($countPrePerson)
    {
        $this->countPrePerson = $countPrePerson;

        return $this;
    }

    /**
     * Get countPrePerson
     *
     * @return integer 
     */
    public function getCountPrePerson()
    {
        return $this->countPrePerson;
    }

    /**
     * Set countPreDay
     *
     * @param integer $countPreDay
     * @return Vote
     */
    public function setCountPreDay($countPreDay)
    {
        $this->countPreDay = $countPreDay;

        return $this;
    }

    /**
     * Get countPreDay
     *
     * @return integer 
     */
    public function getCountPreDay()
    {
        return $this->countPreDay;
    }

    /**
     * Set countPreOptionPerson
     *
     * @param integer $countPreOptionPerson
     * @return Vote
     */
    public function setCountPreOptionPerson($countPreOptionPerson)
    {
        $this->countPreOptionPerson = $countPreOptionPerson;

        return $this;
    }

    /**
     * Get countPreOptionPerson
     *
     * @return integer 
     */
    public function getCountPreOptionPerson()
    {
        return $this->countPreOptionPerson;
    }

    /**
     * Set countPreOptionDay
     *
     * @param integer $countPreOptionDay
     * @return Vote
     */
    public function setCountPreOptionDay($countPreOptionDay)
    {
        $this->countPreOptionDay = $countPreOptionDay;

        return $this;
    }

    /**
     * Get countPreOptionDay
     *
     * @return integer 
     */
    public function getCountPreOptionDay()
    {
        return $this->countPreOptionDay;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Vote
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Vote
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }
}
