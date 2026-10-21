<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_receives_vue_shell_and_api_requires_login(): void
    {
        $this->get('/')->assertOk()->assertSee('id="app"', false);
        $this->getJson('/api/applications')->assertUnauthorized();
    }
}
