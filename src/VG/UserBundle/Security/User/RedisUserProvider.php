<?php

namespace VG\UserBundle\Security\User;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class RedisUserProvider implements UserProviderInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function loadUserByUsername($email)
    {

        $redis = $this->container->get('pdl.phpredis.twitter');
        //var_dump($email);
        $userData = $redis->hMget('user:'.$email, ['name', 'password', 'salt', 'roles']);

        // pretend it returns an array on success, false if there is no user

        if ($userData['password']) {
            $password = $userData['password'];
            $salt = $userData['salt'];
            $roles = $userData['roles'];

            return new RedisUser($email, $password, $salt, $roles);
        }

        throw new UsernameNotFoundException(
            sprintf('Email "%s" does not exist.', $email)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof RedisUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'VG\UserBundle\Security\User\RedisUser';
    }
}