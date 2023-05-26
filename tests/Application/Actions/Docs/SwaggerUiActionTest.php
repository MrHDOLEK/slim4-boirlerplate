<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Docs;

use Tests\TestCase;

class SwaggerUiActionTest extends TestCase
{
    public function testSwaggerUiAction(): void
    {
        $app = $this->getAppInstance();

        $request = $this->createRequest("GET", "/docs/v1");
        $response = $app->handle($request);

        $this->assertStringContainsString("<!DOCTYPE html>", (string)$response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
