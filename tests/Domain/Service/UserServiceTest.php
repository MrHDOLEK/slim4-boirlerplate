<?php

declare(strict_types=1);

namespace Tests\Domain\Service;

use App\Domain\Entity\StoragePersisterInterface;
use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Entity\User\UsersCollection;
use App\Domain\Service\User\UserEventsService;
use App\Domain\Service\User\UserService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    public static function userProvider(): array
    {
        $user = new User(
            "test",
            "test",
            "test",
        );

        return [
            [
                $user,
            ],
        ];
    }

    #[DataProvider("userProvider")]
    public function testGetAllUsersSuccess(User $user): void
    {
        $usersCollection = new UsersCollection($user);

        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("getAll")
            ->willReturn($usersCollection);

        /** @var StoragePersisterInterface&MockObject $storagePersister */
        $storagePersister = $this->createMock(StoragePersisterInterface::class);
        $storagePersister
            ->expects($this->never())
            ->method("storeChanges");

        /** @var UserEventsService&MockObject $eventServiceMock */
        $eventServiceMock = $this->createMock(UserEventsService::class);
        $eventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        $service = new UserService(
            $userRepoMock,
            $eventServiceMock,
            $storagePersister,
        );
        $result = $service->getAllUsers();

        $this->assertEquals(1, $result->count());
    }

    #[DataProvider("userProvider")]
    public function testGetUserByIdSuccess(User $user): void
    {
        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("findUserOfId")
            ->with(1)
            ->willReturn($user);

        /** @var UserEventsService&MockObject $eventServiceMock */
        $eventServiceMock = $this->createMock(UserEventsService::class);
        $eventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        /** @var StoragePersisterInterface&MockObject $storagePersister */
        $storagePersister = $this->createMock(StoragePersisterInterface::class);
        $storagePersister
            ->expects($this->never())
            ->method("storeChanges");

        $service = new UserService(
            $userRepoMock,
            $eventServiceMock,
            $storagePersister,
        );
        $fetched = $service->getUserById(1);

        $this->assertEquals("Test", $fetched->lastName());
        $this->assertEquals("Test", $fetched->firstName());
        $this->assertEquals("test", $fetched->username());
    }

    public function testGetAllUsersThrowsUserNotFoundException(): void
    {
        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("getAll")
            ->willThrowException(new UserNotFoundException());

        /** @var UserEventsService&MockObject $eventServiceMock */
        $eventServiceMock = $this->createMock(UserEventsService::class);
        $eventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        /** @var StoragePersisterInterface&MockObject $storagePersister */
        $storagePersister = $this->createMock(StoragePersisterInterface::class);
        $storagePersister
            ->expects($this->never())
            ->method("storeChanges");

        $service = new UserService(
            $userRepoMock,
            $eventServiceMock,
            $storagePersister,
        );

        $this->expectException(UserNotFoundException::class);
        $service->getAllUsers();
    }

    public function testGetUserByIdThrowsUserNotFoundException(): void
    {
        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("findUserOfId")
            ->with(1)
            ->willThrowException(new UserNotFoundException());

        /** @var StoragePersisterInterface&MockObject $storagePersister */
        $storagePersister = $this->createMock(StoragePersisterInterface::class);
        $storagePersister
            ->expects($this->never())
            ->method("storeChanges");

        /** @var UserEventsService&MockObject $eventServiceMock */
        $eventServiceMock = $this->createMock(UserEventsService::class);
        $eventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        $service = new UserService(
            $userRepoMock,
            $eventServiceMock,
            $storagePersister,
        );

        $this->expectException(UserNotFoundException::class);
        $service->getUserById(1);
    }

    #[DataProvider("userProvider")]
    public function testUpdateUserSuccess(User $user): void
    {
        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("add")
            ->with($user);

        /** @var StoragePersisterInterface&MockObject $storagePersister */
        $storagePersister = $this->createMock(StoragePersisterInterface::class);
        $storagePersister
            ->expects($this->once())
            ->method("storeChanges");

        /** @var UserEventsService&MockObject $eventServiceMock */
        $eventServiceMock = $this->createMock(UserEventsService::class);
        $eventServiceMock
            ->expects($this->once())
            ->method("userWasUpdated")
            ->with($user);

        $service = new UserService(
            $userRepoMock,
            $eventServiceMock,
            $storagePersister,
        );

        $service->updateUser($user);
    }
}
