<?php

declare(strict_types=1);

namespace App\Application\Exception;

use Slim\Exception\HttpSpecializedException;

final class HttpUnprocessableEntityException extends HttpSpecializedException
{
    protected $code = 422;
}
