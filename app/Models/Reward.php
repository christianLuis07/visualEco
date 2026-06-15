<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'points_required',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'points_required' => 'integer',
            'stock' => 'integer',
        ];
    }

    public function redeems(): HasMany
    {
        return $this->hasMany(RewardRedeem::class);
    }
}
