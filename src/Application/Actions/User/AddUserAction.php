<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Factory\UserFactory;
use App\Domain\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use stdClass;
use Throwable;

#[OA\Post(
    path: "/api/v1/user",
    summary: "Create a new user",
    requestBody: new OA\RequestBody(
        request: "User",
        description: "User data in JSON format",
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/User"),
    ),
    tags: ["user"],
    responses: [
        new OA\Response(
            response: "201",
            description: "User created",
            content: new OA\JsonContent(
                type: "object",
                example: new stdClass(),
            ),
        ),
        new OA\Response(response: "422", description: "User data validation error"),
        new OA\Response(response: "401", description: "Unauthorized"),
    ],
)]
/**
 * @throws HttpBadRequestException
 */
class AddUserAction extends UserAction
{
    public function __construct(
        private readonly UserService $userService,
        protected readonly UserFactory $userFactory,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $userData = $this->getFormData(true);

        try {
            $user = $this->userFactory->createFromRequest($userData);
            $this->userService->createUser($user);

            return $this->respondWithJson(null, StatusCodeInterface::STATUS_CREATED);
        } catch (Throwable $exception) {
            throw new HttpBadRequestException(
                $this->request,
                $exception->getMessage(),
                $exception->getPrevious(),
            );
        }
    }
}
