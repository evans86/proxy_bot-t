<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login_from_home(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_home_after_db_login(): void
    {
        User::query()->create([
            'name' => 'Tester',
            'username' => 'paneluser',
            'password' => Hash::make('db-secret'),
        ]);

        $this->get('/login');
        $this->post('/login', [
            'username' => 'paneluser',
            'password' => 'db-secret',
        ])->assertRedirect('/');

        $this->get('/')->assertStatus(200);
    }
}
