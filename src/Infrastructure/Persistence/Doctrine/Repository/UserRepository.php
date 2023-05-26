<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

final class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct(
            $entityManager,
            $entityManager->getClassMetadata(User::class),
        );
    }

    public function findUserOfId(int $id): User
    {
        $user = $this->findOneBy(["id" => $id]);
        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }
        return $user;
    }

    public function findAll(): array
    {
        // TODO: Implement findAll() method.
        return [];
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(User $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }
}
