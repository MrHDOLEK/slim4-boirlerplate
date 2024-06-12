<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Exception\HttpUnprocessableEntityException;
use App\Domain\DomainException\DomainException;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\DomainException\ValidationException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;

class ErrorHandler extends SlimErrorHandler
{
    protected function respond(): Response
    {
        $exception = $this->exception;
        $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
        $error = new ActionError("An internal error has occurred while processing your request.");

        if ($exception instanceof HttpException || $exception instanceof DomainException) {
            $this->handleApplicationException($exception, $error, $statusCode);
        } elseif ($exception instanceof Exception) {
            $this->handleAnyException($exception, $error, $statusCode);
        } else {
            $this->handleError($error, $statusCode);
        }

        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write(json_encode($error, JSON_PRETTY_PRINT));

        return $response->withHeader("Content-Type", "application/json");
    }

    private function handleApplicationException(Exception $exception, ActionError $error, int &$statusCode): void
    {
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

    private function handleAnyException(Exception $exception, ActionError $error, int &$statusCode): void
    {
        $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
        $error->setDescription("An internal error has occurred while processing your request.");

        if ($this->displayErrorDetails) {
            $error->setDescription($exception->getMessage());
        }
    }

    private function handleError(ActionError $error, int &$statusCode): void
    {
        $lastError = error_get_last();

        if ($lastError && !($lastError["type"] === E_ERROR && $this->displayErrorDetails === false)) {
            $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
            $errorMessage = $this->getDetailedErrorMessage(
                $lastError["type"],
                $lastError["message"],
                $lastError["line"],
                $lastError["file"],
            );

            if ($this->displayErrorDetails) {
                $error->setDescription($errorMessage);
            } else {
                $error->setDescription("An error while processing your request. Please try again later.");
            }
        }
    }

    private function getDetailedErrorMessage(int $errorType, string $errorMessage, int $errorLine, string $errorFile): string
    {
        switch ($errorType) {
            case E_USER_ERROR:
                $message = "FATAL ERROR: {$errorMessage}. ";
                $message .= " on line {$errorLine} in file {$errorFile}.";

                break;
            case E_USER_WARNING:
                $message = "WARNING: {$errorMessage}";

                break;
            case E_USER_NOTICE:
                $message = "NOTICE: {$errorMessage}";

                break;
            default:
                $message = "ERROR: {$errorMessage}";
                $message .= " on line {$errorLine} in file {$errorFile}.";

                break;
        }

        return $message;
    }
}
