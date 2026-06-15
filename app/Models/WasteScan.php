<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class WasteScan extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'waste_category_id',
        'image_path',
        'detected_label',
        'confidence_score',
        'status',
        'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'points_awarded' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WasteScan $model): void {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    public function ledger(): MorphOne
    {
        return $this->morphOne(PointLedger::class, 'morphable');
    }
}
