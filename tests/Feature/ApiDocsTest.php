<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    public function test_docs_page_renders(): void
    {
        $this->get('/docs/api')
            ->assertOk()
            ->assertSee('Dokumentasi API HRIS')
            ->assertSee('OpenAPI JSON');
    }

    public function test_openapi_json_is_valid(): void
    {
        $response = $this->get('/docs/api/openapi.json');

        $response->assertOk()->assertHeader('Content-Type', 'application/json');

        $json = $response->json();
        $this->assertSame('3.0.3', $json['openapi']);
        $this->assertArrayHasKey('/v1/login', $json['paths']);
        $this->assertArrayHasKey('/v1/me', $json['paths']);
        $this->assertArrayHasKey('bearerAuth', $json['components']['securitySchemes']);
    }
}
