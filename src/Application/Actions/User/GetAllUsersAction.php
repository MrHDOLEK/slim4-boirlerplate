<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\DTO\Response\UsersResponseDto;
use App\Domain\Service\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

/**
 * @OA\Get(
 *     path="/api/v1/users",
 *     summary="Get all users",
 *     tags={"user"},
 *      @OA\Response(
 *        response="200",
 *        description="Success",
 *          @OA\JsonContent(
 *              type="array",
 *              @OA\Items(ref="#/components/schemas/UserResponseDto")
 *          )
 *      ),
 *     @OA\Response(response = "404", description = "Not Found"),
 *     @OA\Response(response = "500", description = "Internal servel error")
 *)
 *
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
