<?php

namespace Nedwave\Userbundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\NoResultException;
use Nedwave\UserBundle\Doctrine\UserManager;

class UserProvider implements UserProviderInterface
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
            ->setParameter('username', strtolower($username))
            ->setParameter('email', strtolower($username))
            ->getQuery();

        try {
            $user = $query->getSingleResult();
            $user->setLastLogin(new \DateTime());
            $this->userManager->updateUser($user);
            
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }
    
    /**
     * Loads the user for the given Facebook user id.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $facebookUserId The Facebook user id
     * @param string $facebookAccessToken The Facebook access token
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UsernameNotFoundException if the user is not found
     *
     */
    public function loadUserByFacebookUserId($facebookUserId, $facebookAccessToken = null)
    {
        $query = $this->userManager->getRepository()
            ->createQueryBuilder('u')
            ->where('u.facebookUserId = :facebookUserId')
            ->setParameter('facebookUserId', $facebookUserId)
            ->getQuery();

        try {
            $user = $query->getSingleResult();
            $user->setLastLogin(new \DateTime());
            
            if ($facebookAccessToken) {
                $user->setFacebookAccessToken($facebookAccessToken);
            }
            $this->userManager->updateUser($user);
            
        } catch (NoResultException $e) {
            throw new AuthenticationException('Facebook User Id could not be found.');
        }

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
    