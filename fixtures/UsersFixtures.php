<?php

declare(strict_types=1);

namespace fixtures;

use App\Domain\Entity\User\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class UsersFixtures implements FixtureInterface
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $user = new User(
                $this->faker->userName(),
                $this->faker->firstName(),
                $this->faker->lastName(),
            );
            $manager->persist($user);
        }
        $manager->flush();
    }
}
