<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use ArrayAccess;
use Countable;

class UsersCollection implements Countable, ArrayAccess
{
    /** @var array<User> */
    private array $items;

    public function __construct(User ...$users)
    {
        $this->items = $users;
    }

    /**
     * @return array<User>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): User
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}
