<?php

declare(strict_types=1);

namespace App\Application\Actions;

use JsonSerializable;

class ActionPayload implements JsonSerializable
{
    /** @var array|object|null */
    private $data;

    public function __construct(
        private int $statusCode = 200,
        $data = null,
        private ?ActionError $error = null,
    ) {
        $this->data = $data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array|null|object
     */
    public function getData()
    {
        return $this->data;
    }

    public function getError(): ?ActionError
    {
        return $this->error;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        $payload = [
            "statusCode" => $this->statusCode,
        ];

        if ($this->data !== null) {
            $payload["data"] = $this->data;
        } elseif ($this->error instanceof \App\Application\Actions\ActionError) {
            $payload["error"] = $this->error;
        }

        return $payload;
    }
}
