<?php

namespace Tests\Feature\Caisse;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTransactionBySessionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_a_404_if_the_session_does_not_exist()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->getJson('/api/financial-transactions/by-session?session_id=999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Session not found'
                 ]);
    }

    /** @test */
    public function it_returns_400_if_session_id_is_missing()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->getJson('/api/financial-transactions/by-session');

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'session_id is required'
                 ]);
    }
}
