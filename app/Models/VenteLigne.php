<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenteLigne extends Model
{
    use HasFactory;

    protected $table = 'vente_lignes';

    protected $fillable = [
        'vente_id',
        'produit_id',
        'quantite',
        'prix_unitaire_ttc',
        'total_ttc',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire_ttc' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
