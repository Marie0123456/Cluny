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

    public function getTypeDetecteAttribute(): ?string
    {
        $nom = mb_strtolower($this->nom);

        if (str_contains($nom, 'préparatoire') || str_contains($nom, 'prépa') || str_contains($nom, 'preparatoire') || str_contains($nom, 'prepa')) {
            return 'prepa';
        }
        if (str_contains($nom, 'warm-up') || str_contains($nom, 'warm up') || str_contains($nom, 'warmup')) {
            return 'warmup';
        }
        if (str_contains($nom, 'amateur') || str_contains($nom, 'amat')) {
            return 'amateur';
        }
        if (preg_match('/\bpro\b/i', $this->nom)) {
            return 'pro';
        }

        return null;
    }

    public function getBadgeLabelAttribute(): ?string
    {
        return match ($this->type_detecte) {
            'prepa' => 'Prépa',
            'warmup' => 'Warm-up',
            'amateur' => 'Amateur',
            'pro' => 'Pro',
            default => null,
        };
    }

    public function getBadgeCouleurAttribute(): ?string
    {
        return match ($this->type_detecte) {
            'prepa', 'warmup' => 'bg-green-100 text-green-800',
            'amateur' => 'bg-blue-100 text-blue-800',
            'pro' => 'bg-red-100 text-red-800',
            default => null,
        };
    }
}
