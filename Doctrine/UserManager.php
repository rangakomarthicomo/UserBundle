<?php

namespace Nedwave\UserBundle\Doctrine;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManager;

class UserManager
{
    protected $class;
    protected $em;
    protected $encoderFactory;
    protected $repository;

    /**
     * Constructor.
     *
     * @param string                  $class
     * @param EntityManager           $em
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct($class, EntityManager $em, EncoderFactoryInterface $encoderFactory)
    {
        $metadata = $em->getClassMetadata($class);
        $this->class = $metadata->getName();
        
        $this->em = $em;
        $this->encoderFactory = $encoderFactory;
        $this->repository = $em->getRepository($class);
    }
    
    /**
     * Returns an empty user instance
     *
     * @return UserInterface
     */
    public function createUser()
    {
        $class = $this->getClass();
        $user = new $class;

        return $user;
    }
    
    /**
     * Get Class
     *
     * @return Class
     */
    public function getClass()
    {
        return $this->class;
    }
       
    /**
     * Get Encoder
     *
     * @param UserInterface $user
     *
     * @return EncoderFactoryInterface
     */
    protected function getEncoder(UserInterface $user)
    {
        return $this->encoderFactory->getEncoder($user);
    }
    
    /**
     * Get Repository
     *
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }
    
    /**
     * Update and encode user password
     *
     * @param UserInterface $user
     *
     * @return void
     */
    public function updatePassword(UserInterface $user)
    {
        if (0 !== strlen($password = $user->getPassword())) {
            $encoder = $this->getEncoder($user);            
            $password = $encoder->encodePassword($password, $user->getSalt());
            $user->setPassword($password);
        }
    }
    
    /**
     * Update User
     *
     * @param UserInterface $user
     *
     * @return void
     */
    public function updateUser(UserInterface $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}