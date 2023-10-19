<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Entity\User\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = new User(
            "Kamil",
            "AAAA",
            "BBB",
        );
        $manager->persist($user);
        $manager->flush();
    }
}
