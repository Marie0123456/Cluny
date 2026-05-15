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
    ];

    protected $casts = [
        'facture_faite' => 'boolean',
    ];

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function clientFacturation(): BelongsTo
    {
        return $this->belongsTo(ClientFacturation::class);
    }
}
