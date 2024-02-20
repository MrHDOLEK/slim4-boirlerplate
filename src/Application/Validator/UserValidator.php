<?php

declare(strict_types=1);

namespace App\Application\Validator;

use Respect\Validation\Validator as V;

final class UserValidator extends ValidationMiddleware
{
    protected function rules(array $data = []): array
    {
        return [
            "username" => V::allOf(V::stringType(), V::notEmpty(), V::length(1, 255)),
            "firstName" => V::allOf(V::stringType(), V::notEmpty(), V::length(1, 255)),
            "lastName" => V::allOf(V::stringType(), V::notEmpty(), V::length(1, 255)),
        ];
    }

    protected function message(): string
    {
        return "User data validation error";
    }
}
