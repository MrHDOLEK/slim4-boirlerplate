<?php

declare(strict_types=1);

namespace Tests\Domain\Entity\User;

use App\Domain\Entity\User\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function userProvider(): array
    {
        return [
            ["bill.gates", "Bill", "Gates"],
            ["steve.jobs", "Steve", "Jobs"],
            ["mark.zuckerberg", "Mark", "Zuckerberg"],
            ["evan.spiegel", "Evan", "Spiegel"],
            ["jack.dorsey", "Jack", "Dorsey"],
        ];
    }

    /**
     * @dataProvider userProvider
     */
    public function testGetters(string $username, string $firstName, string $lastName): void
    {
        $user = new User($username, $firstName, $lastName);

        $this->assertEquals($username, $user->username());
        $this->assertEquals($firstName, $user->firstName());
        $this->assertEquals($lastName, $user->lastName());
    }

    /**
     * @dataProvider userProvider
     */
    public function testJsonSerialize(string $username, string $firstName, string $lastName): void
    {
        $user = new User($username, $firstName, $lastName);

        $expectedPayload = json_encode([
            "username" => $username,
            "firstName" => $firstName,
            "lastName" => $lastName,
        ]);

        $this->assertEquals($expectedPayload, json_encode($user));
    }
}
