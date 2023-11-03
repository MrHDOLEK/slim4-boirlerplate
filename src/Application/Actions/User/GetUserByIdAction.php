<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\DTO\Response\UserResponseDto;
use App\Domain\Service\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

/**
 * @OA\Get(
 *     path="/api/v1/user/{id}",
 *     summary="Get select user by id",
 *     tags={"user"},
 *     @OA\Parameter(
 *          name="id",
 *          required=true,
 *          in="path",
 *          @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *          response="200",
 *          description="Success",
 *          @OA\JsonContent(ref="#/components/schemas/UserResponseDto")
 *     ),
 *     @OA\Response(response = "404", description = "Not Found"),
 *     @OA\Response(response = "500", description = "Internal servel error")
 *)
 *
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
