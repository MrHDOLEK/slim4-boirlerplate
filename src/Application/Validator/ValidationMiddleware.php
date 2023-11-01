<?php

declare(strict_types=1);

namespace App\Application\Validator;

use App\Application\Factory\ValidationErrorFactory;
use App\Domain\DomainException\ValidationException;
use Awurth\SlimValidation\Validator;
use Awurth\SlimValidation\ValidatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;

abstract class ValidationMiddleware extends Validator implements ValidatorInterface, MiddlewareInterface
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

        $validator = $this->validate($data, $this->rules($data));

        if (!$this->isValid()) {
            throw new ValidationException(
                $this->message(),
                ValidationErrorFactory::create($validator->getErrors()),
            );
        }
    }

    abstract protected function rules(array $data = []): array;

    abstract protected function message(): string;
}
