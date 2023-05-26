<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Docs;

use Tests\TestCase;

class OpenApiDocsActionTest extends TestCase
{
    public function testOpenApiDocsActionTestAction(): void
    {
        $app = $this->getAppInstance();

        $request = $this->createRequest("GET", "/docs/v1/json");
        $response = $app->handle($request);

        $this->assertJson((string)$response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
