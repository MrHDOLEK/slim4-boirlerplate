<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Entity\User\UsersCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct(
            $entityManager,
            $entityManager->getClassMetadata(User::class),
        );
    }

    /**
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User
    {
        $user = $this->findOneBy(["id" => $id]);

        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    /**
     * @throws UserNotFoundException
     */
    public function getAll(): UsersCollection
    {
        $users = $this->createQueryBuilder("o")
            ->select("o")
            ->getQuery()
            ->getResult();

        if ($users === null) {
            throw new UserNotFoundException();
        }

        return new UsersCollection(...$users);
    }

    public function add(User $user): void
    {
        $this->getEntityManager()->persist($user);
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
    }
}
