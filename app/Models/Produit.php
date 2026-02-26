<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prix_ttc',
        'tva',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'prix_ttc' => 'decimal:2',
            'tva' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }

    public function getPrixHtAttribute(): float
    {
        return round($this->prix_ttc / (1 + $this->tva / 100), 2);
    }

    public function venteLignes(): HasMany
    {
        return $this->hasMany(VenteLigne::class);
    }
}
