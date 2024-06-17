<?php

declare(strict_types=1);

namespace App\Infrastructure\Swoole;

use App\Infrastructure\Swoole\Http\RequestCallback;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

class ServerRequestFactory
{
    public static function createRequestCallback(App $app): RequestCallback
    {
        return RequestCallback::fromCallable(
            static fn(ServerRequestInterface $request): ResponseInterface => $app->handle($request),
        );
    }
}
