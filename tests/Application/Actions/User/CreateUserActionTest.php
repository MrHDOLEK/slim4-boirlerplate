<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Service\User\UserEventsService;
use DI\Container;
use Tests\TestCase;

class CreateUserActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getApp();

        /** @var Container $container */
        $container = $app->getContainer();

        $user = new User("steve.jobs", "Steve", "Jobs");

        $userRepositoryProphecy = $this->prophesize(UserRepositoryInterface::class);
        $userEventServiceProphecy = $this->prophesize(UserEventsService::class);
        $userRepositoryProphecy
            ->save($user)
            ->shouldBeCalledOnce();
        $userEventServiceProphecy
            ->userWasCreated($user)
            ->shouldBeCalledOnce();

        $container->set(UserRepositoryInterface::class, $userRepositoryProphecy->reveal());
        $container->set(UserEventsService::class, $userEventServiceProphecy->reveal());

        $request = $this->createRequest(
            method: "POST",
            path: "/api/v1/user",
            body: json_encode(["username" => "steve.jobs", "firstName" => "Steve", "lastName" => "Jobs"]),
        );
        $response = $app->handle($request);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testActionFailureWithInvalidData(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(method: "POST", path: "/api/v1/user", body: json_encode(["username" => "", "firstName" => "", "lastName" => ""]));
        $response = $app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testActionFailureWithoutData(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(method: "POST", path: "/api/v1/user", body: json_encode([]));
        $response = $app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testActionFailureWithoutInvalidHttpMethod(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(method: "DELETE", path: "/api/v1/user", body: json_encode([]));
        $response = $app->handle($request);

        $this->assertSame(405, $response->getStatusCode());
    }
}
