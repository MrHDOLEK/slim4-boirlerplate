<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Factory\UserFactory;
use App\Domain\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Throwable;

/**
 * @OA\Post (
 *     path="/api/v1/user",
 *     summary="Create a new user",
 *     tags={"user"},
 *     @OA\RequestBody(
 *          request="User",
 *          required=true,
 *          description="User data in JSON format",
 *          @OA\JsonContent(ref="#/components/schemas/User"),
 *     ),
 *     @OA\Response(
 *      response="201",
 *      description="User created",
 *      @OA\JsonContent(
 *          type="object",
 *          example={}
 *      )
 *     ),
 *     @OA\Response(response="400", description="User data validation error"),
 *     @OA\Response(response="401", description="Unauthorized")
 * )
 *
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
