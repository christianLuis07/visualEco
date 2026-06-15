<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RewardRedeem;
use App\Models\WasteScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalScans' => WasteScan::count(),
            'pendingRedeems' => RewardRedeem::with(['user', 'reward'])
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->get(),
        ]);
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
