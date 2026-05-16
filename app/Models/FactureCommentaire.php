<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureCommentaire extends Model
{
    protected $table = 'facture_commentaires';

    protected $fillable = [
        'concours_id',
        'client_facturation_id',
        'commentaire',
        'facture_faite',
        'facture_faite_par_id',
        'facture_faite_le',
    ];

    protected $casts = [
        'facture_faite' => 'boolean',
        'facture_faite_le' => 'datetime',
    ];

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function clientFacturation(): BelongsTo
    {
        return $this->belongsTo(ClientFacturation::class);
    }

    public function factureFaitePar(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'facture_faite_par_id');
    }
}
