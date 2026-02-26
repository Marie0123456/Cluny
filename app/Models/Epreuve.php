<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epreuve extends Model
{
    use HasFactory;

    protected $fillable = [
        'concours_id',
        'numero',
        'nom',
        'date',
        'prix',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'prix' => 'decimal:2',
        ];
    }

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }
}
