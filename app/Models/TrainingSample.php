<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TrainingSample extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'user_id',
        'waste_category_id',
        'predicted_category_id',
        'confidence_score',
        'image_path',
        'was_prediction_correct',
        'used_in_training',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score'       => 'decimal:2',
            'was_prediction_correct' => 'boolean',
            'used_in_training'       => 'boolean',
            'created_at'             => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TrainingSample $model): void {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
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
}
