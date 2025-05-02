<?php

declare(strict_types=1);

namespace Tests\Application\Validator;

use App\Application\Validator\UserValidator;
use App\Domain\DomainException\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\TestCase;
use Throwable;

class UserValidatorTest extends TestCase
{
    public function testProcessSuccess(): void
    {
        $validator = new UserValidator();

        $request = $this->createRequest(
            "POST",
            "",
            [],
            [],
            [],
            '{
              "username": "Janusz123",
              "firstName": "Janusz",
              "lastName": "Borowy"
            }',
        );

        /** @var RequestHandlerInterface&MockObject $handlerMock */
        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        /** @var ResponseInterface&MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);

        $handlerMock
            ->expects($this->once())
            ->method("handle")
            ->with($request)
            ->willReturn($responseMock);

        $result = $validator->process($request, $handlerMock);

        $this->assertSame($responseMock, $result);
    }

    public function testProcessThrowsValidationException(): void
    {
        $validator = new UserValidator();

        $request = $this->createRequest(
            "POST",
            "",
            [],
            [],
            [],
            '{
              "firstName": "Janusz",
              "lastName": "' . str_repeat("a", 256) . '"
            }',
        );

        /** @var RequestHandlerInterface&MockObject $handlerMock */
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $handlerMock
            ->expects($this->never())
            ->method("handle");

        try {
            $validator->process($request, $handlerMock);
            $this->fail("Expected ValidationException was not thrown");
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ValidationException::class, $exception);
            $this->assertEquals(
                [
                    "username" => "`NULL` must have a length between 1 and 255",
                    "lastName" => '"' . str_repeat("a", 256) . '" must have a length between 1 and 255',
                ],
                $exception->errors(),
            );
        }
    }
}
