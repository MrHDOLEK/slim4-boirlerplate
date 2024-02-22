<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Factory\UserFactory;
use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Service\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Throwable;

/**
 * @OA\Patch (
 *     path="/api/v1/user/{id}",
 *     summary="Update a user",
 *     tags={"user"},
 *      @OA\Parameter(
 *           name="id",
 *           required=true,
 *           in="path",
 *           @OA\Schema(type="string")
 *      ),
 *     @OA\RequestBody(
 *          request="User",
 *          required=true,
 *          description="User data in JSON format",
 *          @OA\JsonContent(ref="#/components/schemas/User"),
 *     ),
 *     @OA\Response(
 *      response="200",
 *      description="User updated",
 *      @OA\JsonContent(
 *          type="object",
 *          example={}
 *      )
 *     ),
 *     @OA\Response(response="422", description="User data validation error"),
 *     @OA\Response(response="400", description="Bad request"),
 *     @OA\Response(response="401", description="Unauthorized")
 * )
 *
 * @throws HttpBadRequestException
 */
class UpdateUserAction extends UserAction
{
    public function __construct(
        private readonly UserService   $userService,
        protected readonly UserFactory $userFactory,
        protected LoggerInterface      $logger,
    )
    {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $userId = (int)$this->resolveArg("id");
        $userData = $this->getFormData(true);

        try {
            $user = $this->userService->getUserById($userId);
            $user = $this->userFactory->updateFromRequest($user, $userData);
            $this->userService->updateUser($user);

            return $this->respondWithJson();
        } catch (UserNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new HttpBadRequestException(
                $this->request,
                $exception->getMessage(),
                $exception->getPrevious(),
            );
        }
    }
}
