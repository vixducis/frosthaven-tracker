<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_to_campaigns_when_authenticated()
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('campaigns.index'));
    }

    public function test_redirects_to_login_when_unauthenticated()
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }
}
