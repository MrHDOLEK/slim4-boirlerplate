<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\JsonRenderer;
use App\Application\DTO\Response\UserResponseDto;
use App\Domain\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
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
class GetUserByIdAction
{
    public function __construct(
        private readonly UserService $userService,
        protected LoggerInterface $logger,
        private readonly JsonRenderer $renderer,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $userId,
    ): ResponseInterface {
        $userId = (int)$userId;
        $user = $this->userService->getUserById($userId);

        return $this->renderer->json($response, new UserResponseDto($user));
    }
}
