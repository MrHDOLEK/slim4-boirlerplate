<?php

declare(strict_types=1);

namespace App\Domain\Entity;

interface StoragePersisterInterface
{
    public function storeChanges(): void;
}
