<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Application\Handlers\HttpErrorHandler;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserNotFoundException;
use App\Domain\Entity\User\UserRepositoryInterface;
use DI\Container;
use Slim\Middleware\ErrorMiddleware;
use Tests\TestCase;

class ViewUserActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getAppInstance();

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

    public function testActionThrowsUserNotFoundException(): void
    {
        $app = $this->getAppInstance();

        $callableResolver = $app->getCallableResolver();
        $responseFactory = $app->getResponseFactory();

        $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);
        $errorMiddleware = new ErrorMiddleware($callableResolver, $responseFactory, true, false, false);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $app->add($errorMiddleware);

        /** @var Container $container */
        $container = $app->getContainer();

        $userRepositoryProphecy = $this->prophesize(UserRepositoryInterface::class);
        $userRepositoryProphecy
            ->findUserOfId(1)
            ->willThrow(new UserNotFoundException())
            ->shouldBeCalledOnce();

        $container->set(UserRepositoryInterface::class, $userRepositoryProphecy->reveal());

        $request = $this->createRequest("GET", "/api/v1/user/1");
        $response = $app->handle($request);

        $payload = (string)$response->getBody();
        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "The user you requested does not exist.");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
