<?php

namespace Nedwave\Userbundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use HWI\Bundle\OAuthBundle\Connect\AccountConnectorInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Doctrine\ORM\NoResultException;

use Nedwave\UserBundle\Doctrine\UserManager;

class UserProvider implements UserProviderInterface, AccountConnectorInterface, OAuthAwareUserProviderInterface
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * Constructor.
     *
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }
    
    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UsernameNotFoundException if the user is not found
     *
     */
    public function loadUserByUsername($username)
    {
        $query = $this->userManager->getRepository()
            ->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery();

        try {
            $user = $query->getSingleResult();
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }
    
    /**
     * Loads the user by a given UserResponseInterface object.
     *
     * @param UserResponseInterface $response
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $resourceOwnerName = $response->getResourceOwner()->getName();
        
        $user = $this->userManager->getRepository()->findOneBy(array(
            $resourceOwnerName . 'Id' => $response->getUsername()
        ));

        if (null === $user) {
            throw new UsernameNotFoundException('security.login.oauth_user_not_found');
        }
        
        $setResourceAccessToken = 'set' . ucfirst($resourceOwnerName) . 'AccessToken';
        $user->$setResourceAccessToken($response->getAccessToken());
        $this->userManager->updateUser($user);

        return $user;
    }
    
    /**
     * Connects the response the the user object.
     *
     * @param UserInterface         $user     The user object
     * @param UserResponseInterface $response The oauth response
     */
    public function connect(UserInterface $user, UserResponseInterface $response)
    {
        $resourceOwnerName = $response->getResourceOwner()->getName();
        
        $setResource = 'set' . ucfirst($resourceOwnerName);
        $setResourceId = $setResource . 'Id';
        $setResourceAccessToken = $setResource . 'AccessToken';
        
        if (null !== $previousUser = $this->userManager->getRepository()->findOneBy(array($resourceOwnerName . 'Id' => $response->getUsername()))) {
            $previousUser->$setResourceId(null);
            $previousUser->$setResourceAccessToken(null);
            $this->userManager->updateUser($previousUser);
        }
        
        $user->$setResourceId($response->getUsername());
        $user->$setResourceAccessToken($response->getAccessToken());
        
        $this->userManager->updateUser($user);

        return $user;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     * @param UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->userManager->getRepository()->find($user->getId());
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $this->userManager->getClass() === $class
            || is_subclass_of($class, $this->getEntityName());
    }
}
    