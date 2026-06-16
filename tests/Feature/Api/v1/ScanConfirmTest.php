<?php

namespace Tests\Feature\Api\v1;

use App\Models\TrainingSample;
use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteScan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanConfirmTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WasteCategory $plastik;
    private WasteCategory $kaca;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::create([
            'name'     => 'Tester Confirm',
            'email'    => 'confirm@visueco.test',
            'password' => bcrypt('password'),
        ]);

        // id tidak fillable di model; set eksplisit lalu save agar deterministik.
        $this->plastik = new WasteCategory([
            'name' => 'Plastik', 'base_points' => 10, 'handling_instructions' => ['Bersihkan'],
        ]);
        $this->plastik->id = 1;
        $this->plastik->save();

        $this->kaca = new WasteCategory([
            'name' => 'Kaca', 'base_points' => 12, 'handling_instructions' => ['Bilas'],
        ]);
        $this->kaca->id = 4;
        $this->kaca->save();
    }

    private function endpoint(): string
    {
        return '/api/v1/scan/confirm';
    }

    /** Buat WasteScan + file gambar tersimpan, kembalikan modelnya. */
    private function makeScan(int $predictedCategoryId = 1): WasteScan
    {
        $path = 'visueco-scans/dummy.jpg';
        Storage::disk('public')->put($path, 'fake-image-bytes');

        return WasteScan::create([
            'user_id'           => $this->user->id,
            'waste_category_id' => $predictedCategoryId,
            'image_path'        => $path,
            'detected_label'    => 'Botol',
            'confidence_score'  => 0.80,
            'status'            => 'approved',
            'points_awarded'    => 10,
        ]);
    }

    private function fakeMlLearn(): void
    {
        Http::fake([
            '*/learn' => Http::response([
                'success' => true,
                'stored_path' => '/app/model_store/dataset/1/x.jpg',
                'dataset_counts' => ['1' => 1],
            ], 200),
        ]);
    }

    public function test_confirm_correct_prediction_creates_training_sample(): void
    {
        $this->fakeMlLearn();
        $scan = $this->makeScan(predictedCategoryId: 1);

        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint(), [
                'scan_id'             => $scan->id,
                'correct_category_id' => 1, // sama dengan prediksi
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.was_correct', true);

        $this->assertDatabaseCount('training_samples', 1);
        $this->assertDatabaseHas('training_samples', [
            'user_id'                => $this->user->id,
            'waste_category_id'      => 1,
            'predicted_category_id'  => 1,
            'was_prediction_correct' => true,
        ]);
    }

    public function test_confirm_correction_records_mismatch(): void
    {
        $this->fakeMlLearn();
        $scan = $this->makeScan(predictedCategoryId: 1); // model tebak Plastik

        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint(), [
                'scan_id'             => $scan->id,
                'correct_category_id' => 4, // user koreksi jadi Kaca
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.was_correct', false);

        $this->assertDatabaseHas('training_samples', [
            'waste_category_id'      => 4,
            'predicted_category_id'  => 1,
            'was_prediction_correct' => false,
        ]);
    }

    public function test_confirm_rejects_invalid_scan_id(): void
    {
        $this->fakeMlLearn();

        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint(), [
                'scan_id'             => '00000000-0000-0000-0000-000000000000',
                'correct_category_id' => 1,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('training_samples', 0);
    }

    public function test_confirm_rejects_duplicate_confirmation(): void
    {
        $this->fakeMlLearn();
        $scan = $this->makeScan();

        // Konfirmasi pertama
        $this->actingAs($this->user)->postJson($this->endpoint(), [
            'scan_id'             => $scan->id,
            'correct_category_id' => 1,
        ])->assertStatus(201);

        // Konfirmasi kedua pada scan yang sama → ditolak
        $this->actingAs($this->user)->postJson($this->endpoint(), [
            'scan_id'             => $scan->id,
            'correct_category_id' => 1,
        ])->assertStatus(422);

        $this->assertDatabaseCount('training_samples', 1);
    }

    public function test_confirm_rejects_unauthenticated(): void
    {
        $scan = $this->makeScan();

        $this->postJson($this->endpoint(), [
            'scan_id'             => $scan->id,
            'correct_category_id' => 1,
        ])->assertStatus(401);

        $this->assertDatabaseCount('training_samples', 0);
    }
}
