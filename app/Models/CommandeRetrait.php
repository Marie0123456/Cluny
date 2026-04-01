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
        'retired_by',
        'retired_at',
    ];

    protected function casts(): array
    {
        return [
            'date_commande' => 'date',
            'quantite' => 'integer',
            'retire' => 'boolean',
            'retired_at' => 'datetime',
        ];
    }

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function retiredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retired_by');
    }
}
