<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\DTO\Response\UserResponseDto;
use App\Domain\Service\User\UserService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

#[OA\Get(
    path: "/api/v1/user/{id}",
    summary: "Get select user by id",
    tags: ["user"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string"),
        ),
    ],
    responses: [
        new OA\Response(
            response: "200",
            description: "Success",
            content: new OA\JsonContent(ref: "#/components/schemas/UserResponseDto"),
        ),
        new OA\Response(response: "404", description: "Not Found"),
        new OA\Response(response: "500", description: "Internal server error"),
    ],
)]
/**
 * @throws HttpBadRequestException
 */
class GetUserByIdAction extends UserAction
{
    public function __construct(
        private readonly UserService $userService,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $userId = (int)$this->resolveArg("id");
        $user = $this->userService->getUserById($userId);

        return $this->respondWithJson(new UserResponseDto($user));
    }
}
