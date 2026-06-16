<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\AiServiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ScanTrashRequest;
use App\Models\PointLedger;
use App\Models\WasteScan;
use App\Services\AiPredictorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScanController extends Controller
{
    public function __construct(
        private readonly AiPredictorService $aiService,
    ) {}

    public function __invoke(ScanTrashRequest $request): JsonResponse
    {
        try {
            $prediction = $this->aiService->analyzeTrashImage(
                $request->file('image')
            );

            // Anti-Fraud: tolak objek non-recyclable atau confidence rendah.
            // Ambang 0.40 disesuaikan dengan karakteristik MobileNetV2 pada foto
            // dunia nyata (skor agregat lintas-crop) — 0.60 terlalu ketat & menolak
            // objek sah seperti botol di latar polos.
            if (! $prediction['is_recyclable'] || $prediction['confidence_score'] < 0.40) {
                return response()->json([
                    'success' => false,
                    'message' => 'Objek tidak dikenali sebagai sampah yang dapat didaur ulang.',
                    'data'    => [
                        'detected_item'    => $prediction['detected_item'],
                        'confidence_score' => $prediction['confidence_score'],
                        'is_recyclable'    => $prediction['is_recyclable'],
                    ],
                ], 422);
            }

            $user = $request->user();
            $pointsAwarded = $this->resolvePoints($prediction);

            /** @var array{scan: WasteScan, ledger: PointLedger} $result */
            $result = DB::transaction(function () use ($request, $prediction, $user, $pointsAwarded): array {
                $imagePath = $request->file('image')
                    ->store('visueco-scans', 'public');

                $scan = WasteScan::create([
                    'user_id'           => $user->id,
                    'waste_category_id' => $prediction['category_id'],
                    'image_path'        => $imagePath,
                    'detected_label'    => $prediction['detected_item'],
                    'confidence_score'  => $prediction['confidence_score'],
                    'status'            => 'approved',
                    'points_awarded'    => $pointsAwarded,
                ]);

                $ledger = PointLedger::create([
                    'user_id'        => $user->id,
                    'type'           => 'credit',
                    'amount'         => $pointsAwarded,
                    'description'    => "Scan sampah: {$prediction['detected_item']}",
                    'morphable_id'   => $scan->id,
                    'morphable_type' => WasteScan::class,
                ]);

                // Increment atomic — aman dari race condition
                $user->increment('points_balance', $pointsAwarded);

                return compact('scan', 'ledger');
            });

            return response()->json([
                'success' => true,
                'message' => 'Scan berhasil diproses dan poin telah ditambahkan.',
                'data'    => [
                    'scan_id'          => $result['scan']->id,
                    'detected_item'    => $prediction['detected_item'],
                    'category_id'      => $prediction['category_id'],
                    'category_name'    => $prediction['category_name'],
                    'confidence_score' => $prediction['confidence_score'],
                    'is_recyclable'    => $prediction['is_recyclable'],
                    'instructions'     => $prediction['instructions'],
                    'points_awarded'   => $pointsAwarded,
                    'points_balance'   => $user->fresh()->points_balance,
                ],
            ], 201);

        } catch (AiServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 503);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal pada server.',
            ], 500);
        }
    }

    /**
     * Tentukan poin berdasarkan base_points kategori, fallback ke skor AI.
     */
    private function resolvePoints(array $prediction): int
    {
        $category = \App\Models\WasteCategory::find($prediction['category_id']);

        return $category?->base_points ?? (int) ceil($prediction['confidence_score'] * 10);
    }
}
