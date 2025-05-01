<?php

declare(strict_types=1);

namespace Tests\Application\Factory;

use App\Application\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    public static function userData(): array
    {
        return [[[
            "username" => "Janusz123",
            "firstName" => "Janusz",
            "lastName" => "Borowy",
        ]]];
    }

    #[DataProvider("userData")]
    public function testCreateSuccess(array $userData): void
    {
        $user = UserFactory::createFromRequest($userData);

        $this->assertEquals("Janusz", $user->firstName());
        $this->assertEquals("Borowy", $user->lastName());
        $this->assertEquals("janusz123", $user->username());
    }
}
