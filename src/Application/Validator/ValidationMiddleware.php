<?php

declare(strict_types=1);

namespace App\Application\Validator;

use App\Domain\DomainException\ValidationException;
use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Awurth\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;

abstract class ValidationMiddleware implements MiddlewareInterface
{
    /**
     * @throws ValidationException
     * @throws HttpBadRequestException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->validateRequest($request);

        return $handler->handle($request);
    }

    /**
     * @throws ValidationException
     * @throws HttpBadRequestException
     */
    protected function validateRequest(ServerRequestInterface $request): void
    {
        $data = json_decode((string)$request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpBadRequestException($request, "Malformed JSON input.");
        }

        $validator = Validator::create();
        $failures = $validator->validate($data, $this->rules($data));

        if ($failures->count() > 0) {
            $failuresMessage = $this->aggregateFailures($failures);

            throw new ValidationException(
                $this->message(),
                $failuresMessage,
            );
        }
    }

    abstract protected function rules(array $data = []): array;

    abstract protected function message(): string;

    private function aggregateFailures(ValidationFailureCollectionInterface $failures): array
    {
        $aggregatedMessages = [];

        foreach ($failures as $failure) {
            $field = $failure->getValidation()->getProperty();

            if (!isset($aggregatedMessages[$field])) {
                $aggregatedMessages[$field] = $failure->getMessage();
            }
        }

        return $aggregatedMessages;
    }
}
