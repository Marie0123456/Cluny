<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'concours_id',
        'user_id',
        'nom_fichier',
        'format',
        'nb_epreuves',
        'nb_engagements',
        'nb_cavaliers',
        'nb_chevaux',
        'statut',
        'message_erreur',
    ];

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
