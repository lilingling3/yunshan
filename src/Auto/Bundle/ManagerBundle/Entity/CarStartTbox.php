<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CarStartTbox
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class CarStartTbox
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
     * @var RentalCar
     *
     * @ORM\OneToOne(targetEntity="RentalCar")
     */
    private $rentalCar;

    /**
     * @var string
     *
     * @ORM\Column(name="carStartId", type="string", length=255)
     */
    private $carStartId;



    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255,nullable=true)
     */
    private $password;




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
     * Set carStartId
     *
     * @param string $carStartId
     * @return CarStartTbox
     */
    public function setCarStartId($carStartId)
    {
        $this->carStartId = $carStartId;

        return $this;
    }

    /**
     * Get carStartId
     *
     * @return string 
     */
    public function getCarStartId()
    {
        return $this->carStartId;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return CarStartTbox
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
     * Set rentalCar
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar
     * @return CarStartTbox
     */
    public function setRentalCar(\Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar = null)
    {
        $this->rentalCar = $rentalCar;

        return $this;
    }

    /**
     * Get rentalCar
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RentalCar 
     */
    public function getRentalCar()
    {
        return $this->rentalCar;
    }
}
