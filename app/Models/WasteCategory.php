<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_points',
        'handling_instructions',
    ];

    protected function casts(): array
    {
        return [
            'base_points' => 'integer',
            'handling_instructions' => 'array',
        ];
    }

    public function scans(): HasMany
    {
        return $this->hasMany(WasteScan::class);
    }
}
