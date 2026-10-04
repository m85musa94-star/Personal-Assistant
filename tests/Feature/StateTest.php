<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        $u = new User(['name' => $email, 'email' => $email, 'password' => 'correct-horse-1']);
        $u->permissions = ['tasks.use'];
        $u->save();

        return $u;
    }

    public function test_empty_state_for_new_user(): void
    {
        $this->actingAs($this->user('a@example.com'))->getJson('/api/state')
            ->assertOk()->assertJson(['data' => null, 'ts' => 0]);
    }

    public function test_save_and_read_back(): void
    {
        $a = $this->user('a@example.com');
        $doc = ['tasks' => [['id' => 'x1', 'title' => 'مهمة']], 'projects' => ['م1']];
        $this->actingAs($a)->putJson('/api/state', ['data' => $doc, 'ts' => 1000])->assertOk();
        $this->actingAs($a)->getJson('/api/state')->assertOk()
            ->assertJson(['ts' => 1000, 'data' => ['tasks' => [['title' => 'مهمة']]]]);
    }

    public function test_users_are_isolated(): void
    {
        $a = $this->user('a@example.com');
        $b = $this->user('b@example.com');
        $this->actingAs($a)->putJson('/api/state', ['data' => ['tasks' => [['title' => 'سر أحمد']]], 'ts' => 5])->assertOk();
        $this->actingAs($b)->getJson('/api/state')->assertOk()->assertJson(['data' => null]);
        $this->actingAs($b)->putJson('/api/state', ['data' => ['tasks' => []], 'ts' => 9])->assertOk();
        $this->actingAs($a)->getJson('/api/state')->assertJsonPath('data.tasks.0.title', 'سر أحمد');
    }

    public function test_older_write_does_not_overwrite_newer(): void
    {
        $a = $this->user('a@example.com');
        $this->actingAs($a)->putJson('/api/state', ['data' => ['tasks' => [['title' => 'جديد']]], 'ts' => 2000])->assertOk();
        $this->actingAs($a)->putJson('/api/state', ['data' => ['tasks' => [['title' => 'قديم']]], 'ts' => 1000])
            ->assertStatus(409)->assertJsonPath('data.tasks.0.title', 'جديد')->assertJsonPath('ts', 2000);
        $this->actingAs($a)->getJson('/api/state')->assertJsonPath('data.tasks.0.title', 'جديد');
    }

    public function test_validation(): void
    {
        $a = $this->user('a@example.com');
        $this->actingAs($a)->putJson('/api/state', ['data' => 'x', 'ts' => 1])->assertStatus(422);
        $this->actingAs($a)->putJson('/api/state', ['data' => []])->assertStatus(422);
    }

    public function test_guest_gets_401_json(): void
    {
        $this->getJson('/api/state')->assertStatus(401);
        $this->putJson('/api/state', ['data' => [], 'ts' => 1])->assertStatus(401);
    }
}
