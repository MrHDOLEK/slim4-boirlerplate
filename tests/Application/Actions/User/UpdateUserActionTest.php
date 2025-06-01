<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Service\User\UserEventsService;
use DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UpdateUserActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getApp();
        /** @var Container $container */
        $container = $app->getContainer();

        $user = new User("steve.jobs", "Steve", "Jobs");

        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("findUserOfId")
            ->with(1)
            ->willReturn($user);
        $userRepoMock
            ->expects($this->once())
            ->method("add")
            ->with($user);

        /** @var UserEventsService&MockObject $eventServiceMock */
        $eventServiceMock = $this->createMock(UserEventsService::class);
        $eventServiceMock
            ->expects($this->once())
            ->method("userWasUpdated")
            ->with($user);

        $container->set(UserRepositoryInterface::class, $userRepoMock);
        $container->set(UserEventsService::class, $eventServiceMock);

        $request = $this->createRequest(
            method: "PATCH",
            path: "/api/v1/user/1",
            body: json_encode([
                "username" => "john.doe",
                "firstName" => "John",
                "lastName" => "Doe",
            ]),
        );
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("John", $user->firstName());
        $this->assertSame("Doe", $user->lastName());
        $this->assertSame("john.doe", $user->username());
    }

    public function testActionFailureWhenUserDoesNotExist(): void
    {
        $app = $this->getApp();
        /** @var Container $container */
        $container = $app->getContainer();

        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("findUserOfId")
            ->with(1)
            ->willThrowException(new UserNotFoundException());

        $container->set(UserRepositoryInterface::class, $userRepoMock);

        $request = $this->createRequest(
            method: "PATCH",
            path: "/api/v1/user/1",
            body: json_encode([
                "username" => "john.doe",
                "firstName" => "John",
                "lastName" => "Doe",
            ]),
        );
        $response = $app->handle($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testActionFailureWithoutData(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(
            method: "PATCH",
            path: "/api/v1/user/1",
        );
        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testActionFailureWithInvalidData(): void
    {
        $app = $this->getApp();
        /** @var Container $container */
        $container = $app->getContainer();

        $user = new User("steve.jobs", "Steve", "Jobs");

        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->method("findUserOfId")
            ->with(1)
            ->willReturn($user);

        $container->set(UserRepositoryInterface::class, $userRepoMock);

        $request = $this->createRequest(
            method: "PATCH",
            path: "/api/v1/user/1",
            body: json_encode([
                "username" => "",
                "firstName" => "",
                "lastName" => "",
            ]),
        );
        $response = $app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testActionFailureWithInvalidHttpMethod(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(
            method: "POST",
            path: "/api/v1/user/1",
            body: json_encode([
                "username" => "john.doe",
                "firstName" => "John",
                "lastName" => "Doe",
            ]),
        );
        $response = $app->handle($request);

        $this->assertSame(405, $response->getStatusCode());
    }
}
