<?php
namespace VG\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use VG\UserBundle\Entity\User;

class ContentFixtures implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1;
    }

    public function load(ObjectManager $manager)
    {
        $admin = new User(array('ROLE_ADMIN'));
        $admin->setName('admin');
        $admin->setEmail('admin@admin.ua');
        $admin->setSalt(md5(time()));
        $encoder = new MessageDigestPasswordEncoder('sha512', true, 10);
        $password = $encoder->encodePassword('123456', $admin->getSalt());
        $admin->setPassword($password);

        $manager->persist($admin);

        $manager->flush();
    }

}