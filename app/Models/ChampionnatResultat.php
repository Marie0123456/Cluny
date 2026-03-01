<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampionnatResultat extends Model
{
    protected $fillable = [
        'championnat_id',
        'epreuve_id',
        'cavalier_id',
        'cheval_id',
        'points',
        'temps',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'temps' => 'decimal:2',
        ];
    }

    public function championnat(): BelongsTo
    {
        return $this->belongsTo(Championnat::class);
    }

    public function epreuve(): BelongsTo
    {
        return $this->belongsTo(Epreuve::class);
    }

    public function cavalier(): BelongsTo
    {
        return $this->belongsTo(Cavalier::class);
    }

    public function cheval(): BelongsTo
    {
        return $this->belongsTo(Cheval::class);
    }
}
