<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Exception;

use App\Infrastructure\Messaging\Envelope;
use RuntimeException;
use Throwable;

final class MessageSerializationFailure extends RuntimeException
{
    public static function classNotAllowed(string $class): self
    {
        return new self(sprintf('Refusing to unserialize a message body containing "%s": the class is not on the allowlist', $class));
    }

    public static function couldNotDecode(Throwable $previous): self
    {
        return new self(
            sprintf("Refusing to unserialize the message body: %s", $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function notAnEnvelope(string $type): self
    {
        return new self(sprintf("Decoded a message body of type %s, expected an implementation of %s", $type, Envelope::class));
    }

    public static function unknownEventType(?string $eventType): self
    {
        return new self(sprintf('Header "event-type" does not name an implementation of %s, got "%s"', Envelope::class, $eventType ?? "null"));
    }

    public static function couldNotHydrate(string $eventType, Throwable $previous): self
    {
        return new self(
            sprintf('Could not hydrate "%s" from the decoded record: %s', $eventType, $previous->getMessage()),
            0,
            $previous,
        );
    }
}
