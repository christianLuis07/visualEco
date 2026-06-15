<?php

namespace Tests\Feature\Api\v1;

use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanTrashTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WasteCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::create([
            'name'     => 'Tester Visueco',
            'email'    => 'tester@visueco.test',
            'password' => bcrypt('password'),
        ]);

        $this->category = WasteCategory::create([
            'name'                  => 'Plastik',
            'base_points'           => 10,
            'handling_instructions' => ['Bersihkan', 'Pisahkan tutup botol'],
        ]);
    }

    // ─── helpers ────────────────────────────────────────────

    private function fakeAiSuccess(): void
    {
        Http::fake([
            'api.ai-ecosort.local/*' => Http::response([
                'detected_item'    => 'Botol Plastik PET',
                'category_id'      => $this->category->id,
                'category_name'    => 'Plastik',
                'type_detail'      => 'PET-1',
                'confidence_score' => 0.92,
                'is_recyclable'    => true,
                'instructions'     => ['Kosongkan isi', 'Remas botol'],
            ], 200),
        ]);
    }

    private function fakeAiFraud(): void
    {
        Http::fake([
            'api.ai-ecosort.local/*' => Http::response([
                'detected_item'    => 'Kucing',
                'category_id'      => 0,
                'category_name'    => 'Unknown',
                'type_detail'      => '-',
                'confidence_score' => 0.95,
                'is_recyclable'    => false,
                'instructions'     => [],
            ], 200),
        ]);
    }

    private function fakeAiServerError(): void
    {
        Http::fake([
            'api.ai-ecosort.local/*' => Http::response('Internal Server Error', 500),
        ]);
    }

    private function fakeAiTimeout(): void
    {
        Http::fake([
            'api.ai-ecosort.local/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException(
                    'Connection timed out'
                );
            },
        ]);
    }

    private function scanEndpoint(): string
    {
        return '/api/v1/scan';
    }

    // ─── Skenario 1: Sukses ────────────────────────────────

    public function test_scan_api_returns_success_and_awards_points(): void
    {
        $this->fakeAiSuccess();

        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), [
                'image' => UploadedFile::fake()->image('botol.jpg'),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Scan berhasil diproses dan poin telah ditambahkan.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'scan_id',
                    'detected_item',
                    'category_name',
                    'confidence_score',
                    'is_recyclable',
                    'instructions',
                    'points_awarded',
                    'points_balance',
                ],
            ]);

        // Verifikasi data masuk ke database
        $this->assertDatabaseCount('waste_scans', 1);
        $this->assertDatabaseHas('waste_scans', [
            'user_id'        => $this->user->id,
            'detected_label' => 'Botol Plastik PET',
            'status'         => 'approved',
        ]);

        $this->assertDatabaseCount('point_ledgers', 1);
        $this->assertDatabaseHas('point_ledgers', [
            'user_id' => $this->user->id,
            'type'    => 'credit',
            'amount'  => 10,
        ]);

        // Verifikasi saldo user bertambah
        $this->user->refresh();
        $this->assertEquals(10, $this->user->points_balance);

        // Verifikasi file tersimpan
        Storage::disk('public')->assertExists(
            $response->json('data.scan_id') ? 'visueco-scans/' : 'visueco-scans/'
        );
        $this->assertNotEmpty(
            Storage::disk('public')->allFiles('visueco-scans')
        );
    }

    // ─── Skenario 2: Validasi gagal ────────────────────────

    public function test_scan_api_returns_422_when_image_is_missing(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['image'],
            ]);
    }

    public function test_scan_api_returns_422_when_file_format_is_invalid(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), [
                'image' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['image'],
            ]);
    }

    public function test_scan_api_returns_422_when_file_exceeds_max_size(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), [
                // 5 MB > batas 4 MB
                'image' => UploadedFile::fake()->image('huge.jpg')->size(5120),
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['image'],
            ]);
    }

    // ─── Skenario 3: Anti-Fraud ────────────────────────────

    public function test_scan_api_blocks_non_recyclable_objects(): void
    {
        $this->fakeAiFraud();

        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), [
                'image' => UploadedFile::fake()->image('kucing.jpg'),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Objek tidak dikenali sebagai sampah yang dapat didaur ulang.',
            ])
            ->assertJsonPath('data.is_recyclable', false);

        // Pastikan TIDAK ada record yang masuk
        $this->assertDatabaseCount('waste_scans', 0);
        $this->assertDatabaseCount('point_ledgers', 0);

        // Saldo user tetap 0
        $this->user->refresh();
        $this->assertEquals(0, $this->user->points_balance);
    }

    public function test_scan_api_blocks_low_confidence_objects(): void
    {
        Http::fake([
            'api.ai-ecosort.local/*' => Http::response([
                'detected_item'    => 'Objek Blur',
                'category_id'      => $this->category->id,
                'category_name'    => 'Plastik',
                'type_detail'      => 'Tidak Jelas',
                'confidence_score' => 0.35,
                'is_recyclable'    => true,
                'instructions'     => [],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), [
                'image' => UploadedFile::fake()->image('blur.jpg'),
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('waste_scans', 0);
        $this->assertDatabaseCount('point_ledgers', 0);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->points_balance);
    }

    // ─── Skenario 4: AI Down ───────────────────────────────

    public function test_scan_api_handles_ai_connection_timeout(): void
    {
        $this->fakeAiTimeout();

        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), [
                'image' => UploadedFile::fake()->image('botol.jpg'),
            ]);

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'Koneksi ke server AI terputus atau timeout.',
            ]);

        $this->assertDatabaseCount('waste_scans', 0);
        $this->assertDatabaseCount('point_ledgers', 0);
    }

    public function test_scan_api_handles_ai_server_error(): void
    {
        $this->fakeAiServerError();

        $response = $this->actingAs($this->user)
            ->postJson($this->scanEndpoint(), [
                'image' => UploadedFile::fake()->image('botol.jpg'),
            ]);

        $response->assertStatus(500)
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('waste_scans', 0);
        $this->assertDatabaseCount('point_ledgers', 0);
    }

    // ─── Guard: Unauthenticated ────────────────────────────

    public function test_scan_api_rejects_unauthenticated_request(): void
    {
        $this->fakeAiSuccess();

        $response = $this->postJson($this->scanEndpoint(), [
            'image' => UploadedFile::fake()->image('botol.jpg'),
        ]);

        $response->assertStatus(401);
    }
}
