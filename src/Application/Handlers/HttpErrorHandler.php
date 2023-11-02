<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Exception\HttpUnprocessableEntityException;
use App\Domain\DomainException\DomainException;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\DomainException\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Throwable;

class HttpErrorHandler extends SlimErrorHandler
{
    protected function respond(): Response
    {
        $exception = $this->exception;
        $statusCode = 500;
        $error = new ActionError("An internal error has occurred while processing your request.");

        if ($exception instanceof HttpException or $exception instanceof DomainException) {
            $statusCode = $exception->getCode();
            $error->setDescription($exception->getMessage());

            if ($exception instanceof HttpNotFoundException or $exception instanceof DomainRecordNotFoundException) {
                $error->setDescription($exception->getMessage());
            } elseif ($exception instanceof HttpMethodNotAllowedException) {
                $error->setDescription(ActionError::NOT_ALLOWED);
            } elseif ($exception instanceof HttpUnauthorizedException) {
                $error->setDescription(ActionError::UNAUTHENTICATED);
            } elseif ($exception instanceof HttpForbiddenException) {
                $error->setDescription(ActionError::INSUFFICIENT_PRIVILEGES);
            } elseif ($exception instanceof HttpBadRequestException) {
                $error->setDescription($exception->getMessage());
            } elseif ($exception instanceof HttpNotImplementedException) {
                $error->setDescription(ActionError::NOT_IMPLEMENTED);
            } elseif ($exception instanceof HttpUnprocessableEntityException) {
                $error->setDescription($exception->getMessage());
            } elseif ($exception instanceof ValidationException) {
                $error->setDescription($exception->getMessage());
                $error->setErrors($exception->errors());
            }
        }

        if (
            !($exception instanceof HttpException)
            && $exception instanceof Throwable
            && $this->displayErrorDetails
        ) {
            $error->setDescription($exception->getMessage());
        }

        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write(json_encode($error, JSON_PRETTY_PRINT));

        return $response->withHeader("Content-Type", "application/json");
    }
}
