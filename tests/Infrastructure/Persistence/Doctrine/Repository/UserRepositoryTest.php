<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Entity\User\UsersCollection;
use fixtures\UsersFixtures;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->addFixtures(UsersFixtures::class);
        parent::setUp();
    }

    public function testFindAllSuccess(): void
    {
        /**
         * @var UserRepositoryInterface $userRepository
         */
        $userRepository = $this->getContainer()->get(UserRepositoryInterface::class);

        $users = $userRepository->findAll();

        $this->assertInstanceOf(UsersCollection::class, $users);
        $this->assertInstanceOf(User::class, $users->offsetGet(1));
    }

    public function testFindUserOfIdThrowsUserNotFound(): void
    {
        /**
         * @var UserRepositoryInterface $userRepository
         */
        $userRepository = $this->getContainer()->get(UserRepositoryInterface::class);

        $this->expectException(UserNotFoundException::class);
        $userRepository->findUserOfId(0);
    }
}
