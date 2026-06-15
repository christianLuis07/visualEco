<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRewardController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string'],
            'points_required' => ['required', 'integer', 'min:1'],
            'stock'           => ['required', 'integer', 'min:0'],
        ]);

        $reward = Reward::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil ditambahkan.',
            'data'    => [
                'id'              => $reward->id,
                'title'           => $reward->title,
                'description'     => $reward->description,
                'points_required' => $reward->points_required,
                'stock'           => $reward->stock,
            ],
        ], 201);
    }
}
