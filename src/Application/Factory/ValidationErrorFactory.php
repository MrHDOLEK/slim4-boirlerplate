<?php

declare(strict_types=1);

namespace App\Application\Factory;

use App\Application\Validator\ValidationError;

class ValidationErrorFactory
{
    /**
     * @return array<ValidationError>
     */
    public static function create(array $errors): array
    {
        $return = [];

        foreach ($errors as $field => $error) {
            $return[] = new ValidationError($field, $error);
        }

        return $return;
    }
}
