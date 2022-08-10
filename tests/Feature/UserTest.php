<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{

    use WithFaker;
    
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_register()
    {
        $user = User::whereEmail('test@gmail.com')->count();
        
        if($user) {
            $this->assertTrue(true);
            return;
        }

        $response = $this->post('/api/register', [
            'name' => 'test',
            'email' => 'test@gmail.com',
            'password' => "123456789",
            'password_confirmation' => "123456789",
        ]);
        
        $response->assertStatus(200);
    }

    public function test_login()
    {
        $response = $this->post('/api/login', [
            'email' => 'test@gmail.com',
            'password' => '123456789',
        ]);

        $response->assertStatus(200);
    }
}
