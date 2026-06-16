<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminRewardController extends Controller
{
    /**
     * READ — daftar semua reward.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => Reward::orderByDesc('id')->get(),
        ]);
    }

    /**
     * READ — satu reward.
     */
    public function show(Reward $reward): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $reward,
        ]);
    }

    /**
     * CREATE — tambah reward.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateReward($request);

        $reward = Reward::create($validated);

        Log::channel('security')->info('admin.reward.created', [
            'admin_id'  => $request->user()->id,
            'reward_id' => $reward->id,
            'title'     => $reward->title,
            'ip'        => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil ditambahkan.',
            'data'    => $this->present($reward),
        ], 201);
    }

    /**
     * UPDATE — ubah reward.
     */
    public function update(Request $request, Reward $reward): JsonResponse
    {
        $validated = $this->validateReward($request, $reward->id);

        $reward->update($validated);

        Log::channel('security')->info('admin.reward.updated', [
            'admin_id'  => $request->user()->id,
            'reward_id' => $reward->id,
            'changes'   => array_keys($validated),
            'ip'        => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil diperbarui.',
            'data'    => $this->present($reward->fresh()),
        ]);
    }

    /**
     * DELETE — hapus reward.
     *
     * Cegah penghapusan bila masih ada riwayat penukaran (integritas data).
     */
    public function destroy(Request $request, Reward $reward): JsonResponse
    {
        if ($reward->redeems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Reward tidak dapat dihapus karena sudah memiliki riwayat penukaran.',
            ], 422);
        }

        $rewardId = $reward->id;
        $title = $reward->title;
        $reward->delete();

        Log::channel('security')->warning('admin.reward.deleted', [
            'admin_id'  => $request->user()->id,
            'reward_id' => $rewardId,
            'title'     => $title,
            'ip'        => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil dihapus.',
        ]);
    }

    /**
     * Aturan validasi reward (dipakai store & update).
     * `title` unik kecuali untuk record yang sedang diubah.
     */
    private function validateReward(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('rewards', 'title')->ignore($ignoreId),
            ],
            'description'     => ['required', 'string', 'max:1000'],
            'points_required' => ['required', 'integer', 'min:1', 'max:1000000'],
            'stock'           => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);
    }

    private function present(Reward $reward): array
    {
        return [
            'id'              => $reward->id,
            'title'           => $reward->title,
            'description'     => $reward->description,
            'points_required' => $reward->points_required,
            'stock'           => $reward->stock,
        ];
    }
}
