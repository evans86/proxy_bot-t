<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_home_after_admin_login()
    {
        $this->get(route('admin.login'));
        $response = $this->post(route('admin.login.store'), [
            'username' => 'testadmin',
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('home'));
        $this->get('/')->assertStatus(200);
    }
}
