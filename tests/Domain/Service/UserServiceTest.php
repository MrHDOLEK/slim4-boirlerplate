<?php

declare(strict_types=1);

namespace Tests\Domain\Service;

use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Entity\User\UsersCollection;
use App\Domain\Service\User\UserEventsService;
use App\Domain\Service\User\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function userProvider(): array
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

    /**
     * @dataProvider userProvider
     */
    public function testGetAllUsersSuccess(User $user): void
    {
        $usersCollection = new UsersCollection($user);

        $userRepositoryProphecy = $this->prophesize(
            UserRepositoryInterface::class,
        );

        $userEventServiceMock = $this->createMock(UserEventsService::class);
        $userEventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        $userRepositoryProphecy->findAll()
            ->willReturn($usersCollection)
            ->shouldBeCalledOnce();

        $userService = new UserService(
            $userRepositoryProphecy->reveal(),
            $userEventServiceMock,
        );

        $users = $userService->getAllUsers();

        $this->assertEquals(1, $users->count());
    }

    /**
     * @dataProvider userProvider
     */
    public function testGetUserByIdSuccess(User $user): void
    {
        $userRepositoryProphecy = $this->prophesize(
            UserRepositoryInterface::class,
        );

        $userEventServiceMock = $this->createMock(UserEventsService::class);
        $userEventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        $userRepositoryProphecy->findUserOfId(1)
            ->willReturn($user)
            ->shouldBeCalledOnce();

        $userService = new UserService(
            $userRepositoryProphecy->reveal(),
            $userEventServiceMock,
        );

        $user = $userService->getUserById(1);

        $this->assertEquals("Test", $user->lastName());
        $this->assertEquals("Test", $user->firstName());
        $this->assertEquals("test", $user->username());
    }

    public function testGetAllUsersThrowsUserNotFoundException(): void
    {
        $userRepositoryProphecy = $this->prophesize(
            UserRepositoryInterface::class,
        );

        $userEventServiceMock = $this->createMock(UserEventsService::class);
        $userEventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        $userRepositoryProphecy->findAll()
            ->willThrow(UserNotFoundException::class)
            ->shouldBeCalledOnce();

        $userService = new UserService(
            $userRepositoryProphecy->reveal(),
            $userEventServiceMock,
        );

        $this->expectException(UserNotFoundException::class);
        $userService->getAllUsers();
    }

    public function testGetUserByIdThrowsUserNotFoundException(): void
    {
        $userRepositoryProphecy = $this->prophesize(
            UserRepositoryInterface::class,
        );

        $userEventServiceMock = $this->createMock(UserEventsService::class);
        $userEventServiceMock
            ->expects($this->never())
            ->method("userWasCreated");

        $userRepositoryProphecy->findUserOfId(1)
            ->willThrow(UserNotFoundException::class)
            ->shouldBeCalledOnce();

        $userService = new UserService(
            $userRepositoryProphecy->reveal(),
            $userEventServiceMock,
        );

        $this->expectException(UserNotFoundException::class);
        $userService->getUserById(1);
    }

    /**
     * @dataProvider userProvider
     */
    public function testUpdateUserSuccess(User $user): void
    {
        $userRepositoryProphecy = $this->prophesize(
            UserRepositoryInterface::class,
        );

        $userEventServiceMock = $this->createMock(UserEventsService::class);
        $userEventServiceMock
            ->expects($this->once())
            ->method("userWasUpdated");
        $userRepositoryProphecy->save($user)
            ->shouldBeCalledOnce();

        $userService = new UserService(
            $userRepositoryProphecy->reveal(),
            $userEventServiceMock,
        );

        $userService->updateUser($user);
    }
}
