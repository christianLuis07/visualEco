<?php

namespace Tests\Feature\Api\v1;

use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeemRewardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Reward $reward;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'     => 'Tester Redeem',
            'email'    => 'redeem@visueco.test',
            'password' => bcrypt('password'),
        ]);

        $this->reward = Reward::create([
            'title'           => 'Voucher Sembako Rp20.000',
            'description'     => 'Voucher belanja sembako senilai Rp20.000',
            'points_required' => 40,
            'stock'           => 5,
        ]);
    }

    private function redeemEndpoint(): string
    {
        return '/api/v1/redeem';
    }

    // ─── Skenario 1: Sukses ────────────────────────────────

    public function test_redeem_api_returns_success_and_deducts_points_and_stock(): void
    {
        $this->user->update(['points_balance' => 100]);

        $response = $this->actingAs($this->user)
            ->postJson($this->redeemEndpoint(), [
                'reward_id' => $this->reward->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Penukaran reward berhasil.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'redeem_id',
                    'reward_title',
                    'points_spent',
                    'redemption_code',
                    'status',
                    'points_balance',
                ],
            ]);

        $this->assertEquals(60, $response->json('data.points_balance'));
        $this->assertEquals(40, $response->json('data.points_spent'));
        $this->assertStringStartsWith('VSEC-', $response->json('data.redemption_code'));

        // Verifikasi database
        $this->user->refresh();
        $this->assertEquals(60, $this->user->points_balance);

        $this->reward->refresh();
        $this->assertEquals(4, $this->reward->stock);

        $this->assertDatabaseCount('reward_redeems', 1);
        $this->assertDatabaseHas('reward_redeems', [
            'user_id'   => $this->user->id,
            'reward_id' => $this->reward->id,
            'status'    => 'pending',
        ]);

        $this->assertDatabaseCount('point_ledgers', 1);
        $this->assertDatabaseHas('point_ledgers', [
            'user_id' => $this->user->id,
            'type'    => 'debit',
            'amount'  => 40,
        ]);
    }

    // ─── Skenario 2: Poin tidak cukup ─────────────────────

    public function test_redeem_api_fails_if_points_are_insufficient(): void
    {
        $this->user->update(['points_balance' => 10]);

        $response = $this->actingAs($this->user)
            ->postJson($this->redeemEndpoint(), [
                'reward_id' => $this->reward->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Saldo poin Anda tidak mencukupi.',
            ]);

        // Saldo dan stok TIDAK berubah
        $this->user->refresh();
        $this->assertEquals(10, $this->user->points_balance);

        $this->reward->refresh();
        $this->assertEquals(5, $this->reward->stock);

        $this->assertDatabaseCount('reward_redeems', 0);
        $this->assertDatabaseCount('point_ledgers', 0);
    }

    // ─── Skenario 3: Stok habis ───────────────────────────

    public function test_redeem_api_fails_if_reward_stock_is_zero(): void
    {
        $this->user->update(['points_balance' => 200]);
        $this->reward->update(['stock' => 0]);

        $response = $this->actingAs($this->user)
            ->postJson($this->redeemEndpoint(), [
                'reward_id' => $this->reward->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Stok reward sudah habis.',
            ]);

        // Saldo TIDAK berubah
        $this->user->refresh();
        $this->assertEquals(200, $this->user->points_balance);

        $this->assertDatabaseCount('reward_redeems', 0);
        $this->assertDatabaseCount('point_ledgers', 0);
    }

    // ─── Skenario 4: Tanpa autentikasi ────────────────────

    public function test_redeem_api_rejects_unauthenticated_user(): void
    {
        $response = $this->postJson($this->redeemEndpoint(), [
            'reward_id' => $this->reward->id,
        ]);

        $response->assertStatus(401);
    }
}
