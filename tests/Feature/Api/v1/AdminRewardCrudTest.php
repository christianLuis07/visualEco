<?php

namespace Tests\Feature\Api\v1;

use App\Models\Reward;
use App\Models\RewardRedeem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRewardCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@visueco.test',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);

        $this->user = User::create([
            'name' => 'Warga', 'email' => 'warga@visueco.test',
            'password' => bcrypt('password'), 'role' => 'user',
        ]);
    }

    private function makeReward(array $overrides = []): Reward
    {
        return Reward::create(array_merge([
            'title'           => 'Voucher Uji',
            'description'     => 'Deskripsi uji',
            'points_required' => 50,
            'stock'           => 10,
        ], $overrides));
    }

    // ─── CREATE ───────────────────────────────────────────

    public function test_admin_can_create_reward(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/rewards', [
            'title'           => 'Voucher Baru',
            'description'     => 'Hadiah keren',
            'points_required' => 100,
            'stock'           => 5,
        ]);

        $response->assertStatus(201)->assertJsonPath('data.title', 'Voucher Baru');
        $this->assertDatabaseHas('rewards', ['title' => 'Voucher Baru']);
    }

    public function test_create_validates_input(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/admin/rewards', [
            'title' => '', 'points_required' => -5, 'stock' => 'abc',
        ])->assertStatus(422);
    }

    // ─── READ ─────────────────────────────────────────────

    public function test_admin_can_list_and_show_rewards(): void
    {
        $reward = $this->makeReward();

        $this->actingAs($this->admin)->getJson('/api/v1/admin/rewards')
            ->assertStatus(200)->assertJsonFragment(['title' => 'Voucher Uji']);

        $this->actingAs($this->admin)->getJson("/api/v1/admin/rewards/{$reward->id}")
            ->assertStatus(200)->assertJsonPath('data.id', $reward->id);
    }

    // ─── UPDATE ───────────────────────────────────────────

    public function test_admin_can_update_reward(): void
    {
        $reward = $this->makeReward();

        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/rewards/{$reward->id}", [
            'title'           => 'Voucher Diperbarui',
            'description'     => 'Deskripsi baru',
            'points_required' => 75,
            'stock'           => 3,
        ]);

        $response->assertStatus(200)->assertJsonPath('data.title', 'Voucher Diperbarui');
        $this->assertDatabaseHas('rewards', ['id' => $reward->id, 'points_required' => 75, 'stock' => 3]);
    }

    public function test_update_allows_same_title_on_self(): void
    {
        $reward = $this->makeReward(['title' => 'Tetap']);

        $this->actingAs($this->admin)->putJson("/api/v1/admin/rewards/{$reward->id}", [
            'title'           => 'Tetap', // judul sama dgn dirinya → boleh
            'description'     => 'x',
            'points_required' => 10,
            'stock'           => 1,
        ])->assertStatus(200);
    }

    public function test_update_rejects_duplicate_title(): void
    {
        $this->makeReward(['title' => 'Sudah Ada']);
        $target = $this->makeReward(['title' => 'Lainnya']);

        $this->actingAs($this->admin)->putJson("/api/v1/admin/rewards/{$target->id}", [
            'title'           => 'Sudah Ada', // bentrok dgn reward lain
            'description'     => 'x',
            'points_required' => 10,
            'stock'           => 1,
        ])->assertStatus(422);
    }

    // ─── DELETE ───────────────────────────────────────────

    public function test_admin_can_delete_reward(): void
    {
        $reward = $this->makeReward();

        $this->actingAs($this->admin)->deleteJson("/api/v1/admin/rewards/{$reward->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('rewards', ['id' => $reward->id]);
    }

    public function test_delete_blocked_when_reward_has_redeems(): void
    {
        $reward = $this->makeReward();
        RewardRedeem::create([
            'user_id'         => $this->user->id,
            'reward_id'       => $reward->id,
            'redemption_code' => 'VSEC-TEST1234',
            'status'          => 'pending',
        ]);

        $this->actingAs($this->admin)->deleteJson("/api/v1/admin/rewards/{$reward->id}")
            ->assertStatus(422);

        // Reward tidak terhapus (integritas data terjaga)
        $this->assertDatabaseHas('rewards', ['id' => $reward->id]);
    }

    // ─── AUTHORIZATION (BFLA) ─────────────────────────────

    public function test_non_admin_cannot_access_reward_crud(): void
    {
        $reward = $this->makeReward();

        $this->actingAs($this->user)->getJson('/api/v1/admin/rewards')->assertStatus(403);
        $this->actingAs($this->user)->postJson('/api/v1/admin/rewards', [])->assertStatus(403);
        $this->actingAs($this->user)->putJson("/api/v1/admin/rewards/{$reward->id}", [])->assertStatus(403);
        $this->actingAs($this->user)->deleteJson("/api/v1/admin/rewards/{$reward->id}")->assertStatus(403);
    }

    public function test_guest_cannot_access_reward_crud(): void
    {
        $this->getJson('/api/v1/admin/rewards')->assertStatus(401);
        $this->postJson('/api/v1/admin/rewards', [])->assertStatus(401);
    }
}
