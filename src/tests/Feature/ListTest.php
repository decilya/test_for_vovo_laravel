<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ListTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_returns_html()
    {
        User::factory()->create([
            'nickname' => 'user1',
            'avatar' => 'avatars/test.jpg'
        ]);

        $response = $this->get('/api/list');

        $response->assertStatus(200);
        $response->assertSee('user1');
        $response->assertSee('<img src="');
    }
}
