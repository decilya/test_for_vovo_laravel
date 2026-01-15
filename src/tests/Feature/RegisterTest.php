<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success()
    {
        $response = $this->post('/api/register', [
            'nickname' => 'testuser',
            'avatar' => UploadedFile::fake()->image('avatar.jpg')
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['nickname' => 'testuser']);
    }

    public function test_register_duplicate_nickname()
    {
        User::factory()->create(['nickname' => 'existing']);

        $response = $this->post('/api/register', [
            'nickname' => 'existing',
            'avatar' => UploadedFile::fake()->image('avatar.jpg')
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['error' => 'The nickname has already been taken.']);
    }

    public function test_register_invalid_avatar()
    {
        $response = $this->post('/api/register', [
            'nickname' => 'testuser',
            'avatar' => 'not-an-image.txt'
        ]);

        $response->assertStatus(400);
    }
}
