<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use DI\Container;
use Tests\TestCase;

class GetUserByIdActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getApp();

        /** @var Container $container */
        $container = $app->getContainer();

        $user = new User("bill.gates", "Bill", "Gates");

        $userRepositoryProphecy = $this->prophesize(UserRepositoryInterface::class);
        $userRepositoryProphecy
            ->findUserOfId(1)
            ->willReturn($user)
            ->shouldBeCalledOnce();

        $container->set(UserRepositoryInterface::class, $userRepositoryProphecy->reveal());

        $request = $this->createRequest("GET", "/api/v1/user/1");
        $response = $app->handle($request);

        $payload = (string)$response->getBody();

        $this->assertEquals('{"username":"bill.gates","firstName":"Bill","lastName":"Gates"}', $payload);
    }
}
