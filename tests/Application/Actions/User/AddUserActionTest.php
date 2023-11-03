<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use DI\Container;
use Tests\TestCase;

class AddUserActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getApp();

        /** @var Container $container */
        $container = $app->getContainer();

        $user = new User("Janusz123", "Janusz", "Borowy");

        $userRepositoryProphecy = $this->prophesize(UserRepositoryInterface::class);
        $userRepositoryProphecy
            ->save($user)
            ->shouldBeCalledOnce();

        $container->set(UserRepositoryInterface::class, $userRepositoryProphecy->reveal());

        $request = $this->createRequest("POST", "/api/v1/user", body: '{ "username": "Janusz123", "firstName": "Janusz", "lastName": "Borowy" }');
        $response = $app->handle($request);

        $payload = (string)$response->getBody();

        $this->assertEquals("", $payload);
    }
}
