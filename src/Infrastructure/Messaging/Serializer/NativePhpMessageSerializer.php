<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Serializer;

use __PHP_Incomplete_Class;
use App\Domain\Entity\User\User;
use App\Domain\Service\User\DomainEvents\UserWasCreated;
use App\Domain\Service\User\DomainEvents\UserWasUpdated;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Exception\MessageSerializationFailure;
use ReflectionObject;
use SplObjectStorage;
use Throwable;

final readonly class NativePhpMessageSerializer implements MessageSerializer
{
    public const DEFAULT_ALLOWED_CLASSES = [
        UserWasCreated::class,
        UserWasUpdated::class,
        User::class,
    ];

    /** @var list<class-string> */
    private array $allowedClasses;

    /**
     * @param list<class-string> $allowedClasses
     */
    public function __construct(array $allowedClasses = self::DEFAULT_ALLOWED_CLASSES)
    {
        $this->allowedClasses = array_values(array_unique($allowedClasses));
    }

    /**
     * @param list<class-string> $allowedClasses
     */
    public function withAllowedClasses(array $allowedClasses): self
    {
        return new self([...$this->allowedClasses, ...$allowedClasses]);
    }

    /**
     * @return list<class-string>
     */
    public function allowedClasses(): array
    {
        return $this->allowedClasses;
    }

    public function encode(Envelope $envelope): string
    {
        return serialize($envelope);
    }

    public function decode(string $body, array $headers): Envelope
    {
        try {
            $decoded = unserialize($body, ["allowed_classes" => $this->allowedClasses]);
        } catch (Throwable $exception) {
            throw MessageSerializationFailure::couldNotDecode($exception);
        }

        $this->guardAgainstDisallowedClasses($decoded, new SplObjectStorage());

        if (!$decoded instanceof Envelope) {
            throw MessageSerializationFailure::notAnEnvelope(get_debug_type($decoded));
        }

        return $decoded;
    }

    private function guardAgainstDisallowedClasses(mixed $value, SplObjectStorage $visited): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->guardAgainstDisallowedClasses($item, $visited);
            }

            return;
        }

        if (!is_object($value)) {
            return;
        }

        if ($value instanceof __PHP_Incomplete_Class) {
            throw MessageSerializationFailure::classNotAllowed($this->nameOfIncompleteClass($value));
        }

        if ($visited->contains($value)) {
            return;
        }

        $visited->attach($value);

        foreach ((new ReflectionObject($value))->getProperties() as $property) {
            if ($property->isStatic() || !$property->isInitialized($value)) {
                continue;
            }

            $this->guardAgainstDisallowedClasses($property->getValue($value), $visited);
        }
    }

    private function nameOfIncompleteClass(__PHP_Incomplete_Class $value): string
    {
        $properties = (array)$value;
        $name = $properties["__PHP_Incomplete_Class_Name"] ?? null;

        return is_string($name) ? $name : "unknown";
    }
}
