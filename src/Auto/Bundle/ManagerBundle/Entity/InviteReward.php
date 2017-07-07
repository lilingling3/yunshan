<?php

namespace Auto\Bundle\ManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints;

/**
 * InviteReward
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class InviteReward
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
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="AuthMember")
     */
    private $invitee;

    /**
     * @var Invite
     *
     * @ORM\ManyToOne(targetEntity="Invite")
     */
    private $relative;

    /**
     * @var RechargeRecord
     *
     * @ORM\ManyToOne(targetEntity="RechargeRecord")
     */
    private $record;

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
     * Set relative
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Invite $relative
     * @return InviteReward
     */
    public function setRelative(\Auto\Bundle\ManagerBundle\Entity\Invite $relative = null)
    {
        $this->relative = $relative;

        return $this;
    }

    /**
     * Get relative
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\Invite 
     */
    public function getRelative()
    {
        return $this->relative;
    }

    /**
     * Set invitee
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\AuthMember $invitee
     * @return InviteReward
     */
    public function setInvitee(\Auto\Bundle\ManagerBundle\Entity\AuthMember $invitee = null)
    {
        $this->invitee = $invitee;

        return $this;
    }

    /**
     * Get invitee
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\AuthMember 
     */
    public function getInvitee()
    {
        return $this->invitee;
    }



    /**
     * Set record
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RechargeRecord $record
     * @return InviteRecord
     */
    public function setRechargeRecord(\Auto\Bundle\ManagerBundle\Entity\RechargeRecord $record = null)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * Get record
     *
     * @return \Auto\Bundle\ManagerBundle\Entity\RechargeRecord 
     */
    public function getRechargeRecord()
    {
        return $this->record;
    }
}
