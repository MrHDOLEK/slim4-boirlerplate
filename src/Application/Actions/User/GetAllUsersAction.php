<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\DTO\Response\UsersResponseDto;
use App\Domain\Service\User\UserService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

#[OA\Get(
    path: "/api/v1/users",
    summary: "Get all users",
    tags: ["user"],
    responses: [
        new OA\Response(
            response: "200",
            description: "Success",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/UserResponseDto"),
            ),
        ),
        new OA\Response(response: "404", description: "Not Found"),
        new OA\Response(response: "500", description: "Internal server error"),
    ],
)]
/**
 * @throws HttpBadRequestException
 */
class GetAllUsersAction extends UserAction
{
    public function __construct(
        private readonly UserService $userService,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $user = $this->userService->getAllUsers();

        return $this->respondWithJson(new UsersResponseDto($user));
    }
}
