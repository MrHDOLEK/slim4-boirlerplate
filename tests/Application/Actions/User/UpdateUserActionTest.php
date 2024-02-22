<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Service\User\UserEventsService;
use DI\Container;
use Tests\TestCase;

class UpdateUserActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getApp();
        /** @var Container $container */
        $container = $app->getContainer();

        $user = new User("steve.jobs", "Steve", "Jobs");
        $userRepository = $this->prophesize(UserRepositoryInterface::class);
        $userEventService = $this->prophesize(UserEventsService::class);

        $userRepository->findUserOfId(1)->willReturn($user);
        $userRepository->save($user)->shouldBeCalledOnce();
        $userEventService->userWasUpdated($user)->shouldBeCalledOnce();

        $container->set(UserRepositoryInterface::class, $userRepository->reveal());
        $container->set(UserEventsService::class, $userEventService->reveal());

        $request = $this->createRequest(
            method: "PATCH",
            path: "/api/v1/user/1",
            body: json_encode(["username" => "john.doe", "firstName" => "John", "lastName" => "Doe"]),
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

        $userRepository = $this->prophesize(UserRepositoryInterface::class);
        $userRepository->findUserOfId(1)->willThrow(UserNotFoundException::class);

        $container->set(UserRepositoryInterface::class, $userRepository->reveal());

        $request = $this->createRequest(
            method: "PATCH",
            path: "/api/v1/user/1",
            body: json_encode(["username" => "john.doe", "firstName" => "John", "lastName" => "Doe"]),
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
        $userRepository = $this->prophesize(UserRepositoryInterface::class);
        $userRepository->findUserOfId(1)->willReturn($user);

        $container->set(UserRepositoryInterface::class, $userRepository->reveal());

        $request = $this->createRequest(
            method: "PATCH",
            path: "/api/v1/user/1",
            body: json_encode(["username" => "", "firstName" => "", "lastName" => ""]),
        );
        $response = $app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testActionFailureWithoutInvalidHttpMethod(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(
            method: "POST",
            path: "/api/v1/user/1",
            body: json_encode(["username" => "john.doe", "firstName" => "John", "lastName" => "Doe"]),
        );
        $response = $app->handle($request);

        $this->assertSame(405, $response->getStatusCode());
    }
}
