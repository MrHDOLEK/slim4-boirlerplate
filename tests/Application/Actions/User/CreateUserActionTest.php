<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Service\User\UserEventsService;
use DI\Container;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CreateUserActionTest extends TestCase
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
            ->method("save")
            ->with($user);

        /** @var UserEventsService&MockObject $eventServiceMock */
        $eventServiceMock = $this->createMock(UserEventsService::class);
        $eventServiceMock
            ->expects($this->once())
            ->method("userWasCreated")
            ->with($user);

        $container->set(UserRepositoryInterface::class, $userRepoMock);
        $container->set(UserEventsService::class, $eventServiceMock);

        $request = $this->createRequest(
            method: "POST",
            path: "/api/v1/user",
            body: json_encode([
                "username" => "steve.jobs",
                "firstName" => "Steve",
                "lastName" => "Jobs",
            ]),
        );
        $response = $app->handle($request);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testActionFailureWithInvalidData(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(
            method: "POST",
            path: "/api/v1/user",
            body: json_encode([
                "username" => "",
                "firstName" => "",
                "lastName" => "",
            ]),
        );
        $response = $app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testActionFailureWithoutData(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(
            method: "POST",
            path: "/api/v1/user",
            body: json_encode([]),
        );
        $response = $app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testActionFailureWithInvalidHttpMethod(): void
    {
        $app = $this->getApp();

        $request = $this->createRequest(
            method: "DELETE",
            path: "/api/v1/user",
            body: json_encode([]),
        );
        $response = $app->handle($request);

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testDocumentationOfEndpoint(): void
    {
        $yamlFile = $this->getOpenApiPatch();

        $validator = (new ValidatorBuilder())
            ->fromYamlFile($yamlFile)
            ->getRoutedRequestValidator();

        $request = $this->createRequest(
            "POST",
            "/api/v1/user",
            headers: [
                "Content-Type" => "application/json",
            ],
            body: json_encode([
                "username" => "steve.jobs",
                "firstName" => "Steve",
                "lastName" => "Jobs",
            ]),
        );

        $address = new OperationAddress("/api/v1/user", "post");
        $validator->validate($address, $request);

        $this->expectNotToPerformAssertions();
    }
}
