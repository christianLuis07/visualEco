<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\AiServiceException;
use App\Http\Controllers\Controller;
use App\Models\RewardRedeem;
use App\Models\WasteScan;
use App\Services\ModelTrainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AdminController extends Controller
{
    public function __construct(
        private readonly ModelTrainerService $trainerService,
    ) {}

    public function index(): View
    {
        return view('admin.dashboard', [
            'totalScans' => WasteScan::count(),
            'pendingRedeems' => RewardRedeem::with(['user', 'reward'])
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->get(),
            'modelStats' => $this->trainerService->stats(),
            'rewards' => \App\Models\Reward::orderByDesc('id')->get(),
        ]);
    }

    /**
     * Picu pelatihan ulang model AI dari seluruh data latih terkumpul.
     */
    public function trainModel(): JsonResponse
    {
        try {
            $version = $this->trainerService->train();

            return response()->json([
                'success' => true,
                'message' => 'Model AI berhasil dilatih ulang.',
                'data' => [
                    'version'      => $version->version,
                    'accuracy'     => $version->accuracy,
                    'sample_count' => $version->sample_count,
                ],
            ]);
        } catch (AiServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() === 422 ? 422 : 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melatih model.',
            ], 500);
        }
    }

    /**
     * Impor foto dari folder seed lalu latih ulang model — satu aksi.
     */
    public function seedTrainModel(): JsonResponse
    {
        try {
            $result = $this->trainerService->seedAndTrain();
            $version = $result['version'];

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimpor {$result['seed_added']} foto dan melatih model.",
                'data' => [
                    'version'      => $version->version,
                    'accuracy'     => $version->accuracy,
                    'sample_count' => $version->sample_count,
                    'seed_added'   => $result['seed_added'],
                    'seed_skipped' => $result['seed_skipped'],
                ],
            ]);
        } catch (AiServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() === 422 ? 422 : 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat impor seed & melatih model.',
            ], 500);
        }
    }

    public function verifyVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'redemption_code' => ['required', 'string'],
        ]);

        $redeem = RewardRedeem::with(['user', 'reward'])
            ->where('redemption_code', $request->input('redemption_code'))
            ->first();

        if (! $redeem) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'              => $redeem->id,
                'redemption_code' => $redeem->redemption_code,
                'status'          => $redeem->status,
                'user_name'       => $redeem->user->name,
                'reward_title'    => $redeem->reward->title,
                'points_spent'    => $redeem->reward->points_required,
                'created_at'      => $redeem->created_at->format('d M Y, H:i'),
            ],
        ]);
    }

    public function completeRedeem(Request $request, string $id): JsonResponse
    {
        try {
            $redeem = DB::transaction(function () use ($id): RewardRedeem {
                $redeem = RewardRedeem::lockForUpdate()->findOrFail($id);

                if ($redeem->status !== 'pending') {
                    throw new \DomainException('Voucher ini sudah berstatus: ' . $redeem->status);
                }

                $redeem->update(['status' => 'completed']);

                return $redeem;
            });

            Log::channel('security')->info('admin.voucher.completed', [
                'admin_id'  => $request->user()->id,
                'redeem_id' => $redeem->id,
                'ip'        => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hadiah berhasil diserahkan. Voucher ditandai selesai.',
                'data' => [
                    'id'     => $redeem->id,
                    'status' => $redeem->status,
                ],
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal pada server.',
            ], 500);
        }
    }
}
