<?php

declare(strict_types=1);

namespace Tests\Application\Factory;

use App\Application\Factory\UserFactory;
use App\Domain\Entity\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    public static function createUserDataProvider(): array
    {
        return [
            "basic_user_data" => [
                "userData" => [
                    "username" => "Janusz123",
                    "firstName" => "janusz",
                    "lastName" => "borowy",
                ],
                "expectedUsername" => "janusz123",
                "expectedFirstName" => "Janusz",
                "expectedLastName" => "Borowy",
            ],
            "user_with_spaces" => [
                "userData" => [
                    "username" => " TestUser ",
                    "firstName" => " jan ",
                    "lastName" => " kowalski ",
                ],
                "expectedUsername" => " testuser ",
                "expectedFirstName" => " jan ",
                "expectedLastName" => " kowalski ",
            ],
            "user_with_empty_strings" => [
                "userData" => [
                    "username" => "",
                    "firstName" => "",
                    "lastName" => "",
                ],
                "expectedUsername" => "",
                "expectedFirstName" => "",
                "expectedLastName" => "",
            ],
            "user_with_special_characters" => [
                "userData" => [
                    "username" => "User@123",
                    "firstName" => "jan-piotr",
                    "lastName" => "kowalski-nowak",
                ],
                "expectedUsername" => "user@123",
                "expectedFirstName" => "Jan-piotr",
                "expectedLastName" => "Kowalski-nowak",
            ],
            "user_with_mixed_case" => [
                "userData" => [
                    "username" => "MiXeDcAsE",
                    "firstName" => "mARIA",
                    "lastName" => "koWALSKA",
                ],
                "expectedUsername" => "mixedcase",
                "expectedFirstName" => "MARIA",
                "expectedLastName" => "KoWALSKA",
            ],
            "user_with_uppercase" => [
                "userData" => [
                    "username" => "UPPERCASE",
                    "firstName" => "ANNA",
                    "lastName" => "NOWAK",
                ],
                "expectedUsername" => "uppercase",
                "expectedFirstName" => "ANNA",
                "expectedLastName" => "NOWAK",
            ],
        ];
    }

    public static function updateUserDataProvider(): array
    {
        return [
            "partial_update_username_only" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => ["username" => "NewUsername"],
                "expectedUsername" => "NewUsername",
                "expectedFirstName" => "OriginalFirst",
                "expectedLastName" => "OriginalLast",
            ],
            "partial_update_firstName_only" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => ["firstName" => "newFirstName"],
                "expectedUsername" => "originaluser",
                "expectedFirstName" => "newFirstName",
                "expectedLastName" => "OriginalLast",
            ],
            "partial_update_lastName_only" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => ["lastName" => "newLastName"],
                "expectedUsername" => "originaluser",
                "expectedFirstName" => "OriginalFirst",
                "expectedLastName" => "newLastName",
            ],
            "partial_update_firstName_and_lastName" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => [
                    "firstName" => "newFirstName",
                    "lastName" => "newLastName",
                ],
                "expectedUsername" => "originaluser",
                "expectedFirstName" => "newFirstName",
                "expectedLastName" => "newLastName",
            ],
            "full_update_with_transformations" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => [
                    "username" => "UPDATED_USER",
                    "firstName" => "updated_first",
                    "lastName" => "UPDATED_LAST",
                ],
                "expectedUsername" => "UPDATED_USER",
                "expectedFirstName" => "updated_first",
                "expectedLastName" => "UPDATED_LAST",
            ],
            "update_with_empty_values" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => [
                    "username" => "",
                    "firstName" => "",
                    "lastName" => "",
                ],
                "expectedUsername" => "",
                "expectedFirstName" => "",
                "expectedLastName" => "",
            ],
            "update_with_special_characters" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => [
                    "username" => "User@Special123",
                    "firstName" => "jan-maria",
                    "lastName" => "kowalski-nowak",
                ],
                "expectedUsername" => "User@Special123",
                "expectedFirstName" => "jan-maria",
                "expectedLastName" => "kowalski-nowak",
            ],
            "update_with_mixed_case" => [
                "originalUser" => new User("OriginalUser", "OriginalFirst", "OriginalLast"),
                "updateData" => [
                    "username" => "MiXeD_CaSe",
                    "firstName" => "mARIA",
                    "lastName" => "koWALSKA",
                ],
                "expectedUsername" => "MiXeD_CaSe",
                "expectedFirstName" => "mARIA",
                "expectedLastName" => "koWALSKA",
            ],
        ];
    }

    public static function updateWithEmptyDataProvider(): array
    {
        return [
            "empty_array" => [
                "originalUser" => new User("TestUser", "TestFirst", "TestLast"),
                "updateData" => [],
            ],
            "array_with_unknown_fields" => [
                "originalUser" => new User("TestUser", "TestFirst", "TestLast"),
                "updateData" => [
                    "unknownField" => "someValue",
                    "anotherUnknown" => "anotherValue",
                ],
            ],
        ];
    }

    public static function objectToPopulateTestDataProvider(): array
    {
        return [
            "simple_update" => [
                "originalUser" => new User("original", "Original", "User"),
                "updateData" => [
                    "username" => "updated",
                    "firstName" => "Updated",
                    "lastName" => "User",
                ],
            ],
            "partial_update_preserves_object" => [
                "originalUser" => new User("original", "Original", "User"),
                "updateData" => [
                    "username" => "newUsername",
                ],
            ],
            "empty_update_preserves_object" => [
                "originalUser" => new User("original", "Original", "User"),
                "updateData" => [],
            ],
        ];
    }

    public static function createAndUpdateComparisonDataProvider(): array
    {
        return [];
    }

    #[DataProvider("createUserDataProvider")]
    public function testCreateFromRequestSuccess(
        array $userData,
        string $expectedUsername,
        string $expectedFirstName,
        string $expectedLastName,
    ): void {
        $user = $this->userFactory->createFromRequest($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($expectedUsername, $user->username());
        $this->assertEquals($expectedFirstName, $user->firstName());
        $this->assertEquals($expectedLastName, $user->lastName());
    }

    public function testCreateFromRequestWithEmptyArrayThrowsMissingConstructorArgumentsException(): void
    {
        $this->expectException(MissingConstructorArgumentsException::class);
        $this->userFactory->createFromRequest([]);
    }

    #[DataProvider("updateUserDataProvider")]
    public function testUpdateFromRequestSuccess(
        User $originalUser,
        array $updateData,
        string $expectedUsername,
        string $expectedFirstName,
        string $expectedLastName,
    ): void {
        $updatedUser = $this->userFactory->updateFromRequest($originalUser, $updateData);

        $this->assertSame($originalUser, $updatedUser);
        $this->assertEquals($expectedUsername, $updatedUser->username());
        $this->assertEquals($expectedFirstName, $updatedUser->firstName());
        $this->assertEquals($expectedLastName, $updatedUser->lastName());
    }

    #[DataProvider("updateWithEmptyDataProvider")]
    public function testUpdateFromRequestWithEmptyData(User $originalUser, array $updateData): void
    {
        $originalUsername = $originalUser->username();
        $OriginalFirstName = $originalUser->firstName();
        $OriginalLastName = $originalUser->lastName();

        $updatedUser = $this->userFactory->updateFromRequest($originalUser, $updateData);

        $this->assertEquals($originalUsername, $updatedUser->username());
        $this->assertEquals($OriginalFirstName, $updatedUser->firstName());
        $this->assertEquals($OriginalLastName, $updatedUser->lastName());
        $this->assertSame($originalUser, $updatedUser);
    }

    #[DataProvider("objectToPopulateTestDataProvider")]
    public function testUpdateFromRequestObjectToPopulate(User $originalUser, array $updateData): void
    {
        $userIdBefore = spl_object_id($originalUser);

        $updatedUser = $this->userFactory->updateFromRequest($originalUser, $updateData);
        $userIdAfter = spl_object_id($updatedUser);

        $this->assertEquals($userIdBefore, $userIdAfter);
        $this->assertSame($originalUser, $updatedUser);
    }
}
