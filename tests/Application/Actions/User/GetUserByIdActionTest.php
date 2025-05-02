<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use DI\Container;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class GetUserByIdActionTest extends TestCase
{
    public function testActionSuccess(): void
    {
        $app = $this->getApp();
        /** @var Container $container */
        $container = $app->getContainer();

        $user = new User("bill.gates", "Bill", "Gates");

        /** @var UserRepositoryInterface&MockObject $userRepoMock */
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $userRepoMock
            ->expects($this->once())
            ->method("findUserOfId")
            ->with(1)
            ->willReturn($user);

        $container->set(UserRepositoryInterface::class, $userRepoMock);

        $request = $this->createRequest("GET", "/api/v1/user/1");
        $response = $app->handle($request);

        $payload = (string)$response->getBody();
        $this->assertSame(
            '{"username":"bill.gates","firstName":"Bill","lastName":"Gates"}',
            $payload,
        );
    }

    public function testDocumentationOfEndpoint(): void
    {
        $yamlFile = $this->getOpenApiPatch();
        $validator = (new ValidatorBuilder())
            ->fromYamlFile($yamlFile)
            ->getRoutedRequestValidator();

        $request = $this->createRequest(
            "GET",
            "/api/v1/user/{id}",
            headers: [
                "Content-Type" => "application/json",
            ],
        );

        $address = new OperationAddress("/api/v1/user/{id}", "get");
        $validator->validate($address, $request);

        $this->expectNotToPerformAssertions();
    }
}
