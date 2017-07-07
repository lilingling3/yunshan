<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/24
 * Time: 下午5:50
 */
namespace Auto\Bundle\Api2Bundle\Security;

use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;


class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{

    public function createToken(Request $request, $providerKey)
    {
        $apiKey = $request->headers->get('userID');

        if (!$apiKey) {

            //throw new BadCredentialsException('No API key found');
            // or to just skip api key authentication
             return null;
        }
        return new PreAuthenticatedToken(
            'anon.',
            $apiKey,
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface
    $userProvider, $providerKey)
    {

        if (!$userProvider instanceof ApiKeyUserProvider) {
            throw new \InvalidArgumentException(
                sprintf('The user provider must be an instance of ApiKeyUserProvider(%s was given)',get_class
                ($userProvider)));
        }


        $apiKey = $token->getCredentials();



        $user = $userProvider->loadUserByUsername($apiKey);

        if(empty($user)){

            return new PreAuthenticatedToken(
                'anon.',
                $apiKey,
                $providerKey
            );

        }


        return new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
    }
        public function supportsToken(TokenInterface $token, $providerKey)
            {
                return $token instanceof PreAuthenticatedToken && $token->getProviderKey() ===
                $providerKey;
            }
}