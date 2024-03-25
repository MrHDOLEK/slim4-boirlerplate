<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Factory\UserFactory;
use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Service\User\UserService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Throwable;

#[OA\Patch(
    path: "/api/v1/user/{id}",
    summary: "Update a user",
    requestBody: new OA\RequestBody(
        request: "User",
        description: "User data in JSON format",
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/User"),
    ),
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
            description: "User updated",
            content: new OA\JsonContent(
                example: [],
            ),
        ),
        new OA\Response(response: "422", description: "User data validation error"),
        new OA\Response(response: "400", description: "Bad request"),
        new OA\Response(response: "401", description: "Unauthorized"),
    ],
)]
/*
* @throws HttpBadRequestException
*/
class UpdateUserAction extends UserAction
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
