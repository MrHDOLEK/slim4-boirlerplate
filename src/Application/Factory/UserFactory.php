<?php

declare(strict_types=1);

namespace App\Application\Factory;

use App\Domain\Entity\User\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final readonly class UserFactory
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
    ) {}

    public function createFromRequest(array $userData): User
    {
        return $this->denormalizer->denormalize($userData, User::class);
    }

    public function updateFromRequest(User $user, array $userData): User
    {
        return $this->denormalizer->denormalize(
            $userData,
            User::class,
            null,
            [
                ObjectNormalizer::OBJECT_TO_POPULATE => $user,
            ],
        );
    }
}
