<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\AiServiceException;
use App\Http\Controllers\Controller;
use App\Models\TrainingSample;
use App\Models\WasteCategory;
use App\Models\WasteScan;
use App\Services\AiPredictorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ScanConfirmController extends Controller
{
    public function __construct(
        private readonly AiPredictorService $aiService,
    ) {}

    /**
     * Warga mengonfirmasi/mengoreksi hasil scan. Gambar + kategori benar
     * dikirim ke ML service sebagai data latih, lalu dicatat sebagai
     * training_sample untuk audit & pelatihan ulang.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scan_id'             => ['required', 'uuid', 'exists:waste_scans,id'],
            'correct_category_id' => ['required', 'integer', 'exists:waste_categories,id'],
        ]);

        $user = $request->user();

        /** @var WasteScan $scan */
        $scan = WasteScan::where('id', $validated['scan_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Hindari duplikasi: satu scan hanya boleh dilatih sekali.
        if (TrainingSample::where('image_path', $scan->image_path)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Scan ini sudah pernah dikonfirmasi.',
            ], 422);
        }

        if (! Storage::disk('public')->exists($scan->image_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas gambar scan tidak ditemukan.',
            ], 404);
        }

        $correctCategoryId = (int) $validated['correct_category_id'];
        $wasCorrect = (int) $scan->waste_category_id === $correctCategoryId;

        try {
            $imageContents = Storage::disk('public')->get($scan->image_path);
            $filename = basename($scan->image_path);

            $this->aiService->learnSample($imageContents, $filename, $correctCategoryId);
        } catch (AiServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim data latih ke server AI.',
            ], 500);
        }

        $sample = TrainingSample::create([
            'user_id'                => $user->id,
            'waste_category_id'      => $correctCategoryId,
            'predicted_category_id'  => $scan->waste_category_id,
            'confidence_score'       => $scan->confidence_score,
            'image_path'             => $scan->image_path,
            'was_prediction_correct' => $wasCorrect,
            'used_in_training'       => false,
        ]);

        $category = WasteCategory::find($correctCategoryId);

        return response()->json([
            'success' => true,
            'message' => $wasCorrect
                ? 'Terima kasih! Konfirmasi Anda membantu AI belajar.'
                : 'Terima kasih atas koreksinya! AI akan belajar dari ini.',
            'data' => [
                'training_sample_id' => $sample->id,
                'correct_category'   => $category?->name,
                'was_correct'        => $wasCorrect,
            ],
        ], 201);
    }
}
