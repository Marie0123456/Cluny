<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommandeRetrait extends Model
{
    use SoftDeletes;

    protected $table = 'commande_retraits';

    protected $fillable = [
        'concours_id',
        'vente_id',
        'numero_commande',
        'date_commande',
        'prenom',
        'nom',
        'produit',
        'quantite',
        'quantite_retiree',
        'emplacement_boxes',
        'note_client',
        'retire',
        'retired_by',
        'retired_at',
    ];

    protected function casts(): array
    {
        return [
            'date_commande' => 'date',
            'quantite' => 'integer',
            'quantite_retiree' => 'integer',
            'retire' => 'boolean',
            'retired_at' => 'datetime',
        ];
    }

    public function getStatutRetraitAttribute(): string
    {
        if ($this->quantite_retiree <= 0) {
            return 'aucun';
        }
        if ($this->quantite_retiree >= $this->quantite) {
            return 'complet';
        }
        return 'partiel';
    }

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function retiredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retired_by');
    }
}
