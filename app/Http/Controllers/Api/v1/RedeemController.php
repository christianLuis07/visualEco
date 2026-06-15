<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PointLedger;
use App\Models\Reward;
use App\Models\RewardRedeem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RedeemController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'reward_id' => ['required', 'exists:rewards,id'],
        ]);

        try {
            $result = DB::transaction(function () use ($request): array {
                $user = $request->user()->lockForUpdate()->find($request->user()->id);
                $reward = Reward::lockForUpdate()->findOrFail($request->input('reward_id'));

                if ($reward->stock <= 0) {
                    throw new \DomainException('Stok reward sudah habis.');
                }

                if ($user->points_balance < $reward->points_required) {
                    throw new \DomainException('Saldo poin Anda tidak mencukupi.');
                }

                $reward->decrement('stock');
                $user->decrement('points_balance', $reward->points_required);

                $redeem = RewardRedeem::create([
                    'user_id'         => $user->id,
                    'reward_id'       => $reward->id,
                    'redemption_code' => 'VSEC-' . Str::upper(Str::random(8)),
                    'status'          => 'pending',
                ]);

                PointLedger::create([
                    'user_id'        => $user->id,
                    'type'           => 'debit',
                    'amount'         => $reward->points_required,
                    'description'    => "Redeem reward: {$reward->title}",
                    'morphable_id'   => $redeem->id,
                    'morphable_type' => RewardRedeem::class,
                ]);

                return [
                    'redeem'  => $redeem,
                    'reward'  => $reward,
                    'balance' => $user->points_balance,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Penukaran reward berhasil.',
                'data'    => [
                    'redeem_id'       => $result['redeem']->id,
                    'reward_title'    => $result['reward']->title,
                    'points_spent'    => $result['reward']->points_required,
                    'redemption_code' => $result['redeem']->redemption_code,
                    'status'          => $result['redeem']->status,
                    'points_balance'  => $result['balance'],
                ],
            ], 201);

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
