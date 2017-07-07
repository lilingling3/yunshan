<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/24
 * Time: 下午6:09
 */

namespace Auto\Bundle\Api2Bundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectManager;

class ApiKeyUserProvider implements UserProviderInterface{

    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }


    public function loadUserByUsername($username)
    {
        $member
            = $this->manager
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token' => $username])
            ;
        return $member;
    }

    
    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }
    public function supportsClass($class)
    {
        return 'Auto\Bundle\ManagerBundle\Entity\Member' === $class;
    }
}

