<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('دفتر كاكيبو');
    }

    public function test_register_login_and_logout(): void
    {
        $this->post('/register', [
            'name' => 'هانا',
            'email' => 'hana@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])->assertRedirect('/');
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->post('/login', ['email' => 'hana@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'hana@example.com', 'password' => 'secret-pass'])->assertRedirect('/');
    }

    public function test_home_opens_the_current_month(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')->assertRedirect('/months/'.now()->format('Y-m'));
    }
}
