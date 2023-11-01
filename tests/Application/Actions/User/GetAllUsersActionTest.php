<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Entity\User\UsersCollection;
use DI\Container;
use Tests\TestCase;

class GetAllUsersActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getApp();

        /** @var Container $container */
        $container = $app->getContainer();

        $users = new UsersCollection(new User("bill.gates", "Bill", "Gates"));

        $userRepositoryProphecy = $this->prophesize(UserRepositoryInterface::class);
        $userRepositoryProphecy
            ->findAll()
            ->willReturn($users)
            ->shouldBeCalledOnce();

        $container->set(UserRepositoryInterface::class, $userRepositoryProphecy->reveal());

        $request = $this->createRequest("GET", "/api/v1/users");
        $response = $app->handle($request);

        $payload = (string)$response->getBody();

        $this->assertEquals('[{"username":"bill.gates","firstName":"Bill","lastName":"Gates"}]', $payload);
    }
}
