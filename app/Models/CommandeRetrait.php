<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandeRetrait extends Model
{
    protected $table = 'commande_retraits';

    protected $fillable = [
        'concours_id',
        'numero_commande',
        'date_commande',
        'prenom',
        'nom',
        'produit',
        'quantite',
        'emplacement_boxes',
        'retire',
    ];

    protected function casts(): array
    {
        return [
            'date_commande' => 'date',
            'retire' => 'boolean',
        ];
    }

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }
}
