<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'sample_count',
        'accuracy',
        'is_active',
        'trained_at',
    ];

    protected function casts(): array
    {
        return [
            'version'      => 'integer',
            'sample_count' => 'integer',
            'accuracy'     => 'decimal:4',
            'is_active'    => 'boolean',
            'trained_at'   => 'datetime',
        ];
    }

    /**
     * Versi model yang sedang aktif dipakai untuk prediksi.
     */
    public static function active(): ?self
    {
        return static::where('is_active', true)->latest('version')->first();
    }
}
