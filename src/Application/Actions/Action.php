<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    protected Request $request;
    protected Response $response;
    protected array $args;

    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage(), $e->getPrevious());
        }
    }

    /**
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @throws HttpBadRequestException
     */
    protected function getFormData(bool $associativeArray = true): object|array
    {
        $input = json_decode((string)$this->request->getBody(), $associativeArray);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpBadRequestException($this->request, "Malformed JSON input.");
        }

        return $input;
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    protected function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * @param array|object|null $data
     */
    protected function respondWithJson($data = null, int $statusCode = StatusCodeInterface::STATUS_OK): Response
    {
        if ($data !== null) {
            $this->response->getBody()->write((string)json_encode($data));
        }

        return $this->response
            ->withHeader("Content-Type", "application/json")
            ->withStatus($statusCode);
    }

    protected function getHeaderByKey(string $headerKey): ?string
    {
        return isset($this->request->getHeader($headerKey)[0]) ? $this->request->getHeader($headerKey)[0] : null;
    }
}
