<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampionnatExclusion extends Model
{
    protected $fillable = [
        'championnat_id',
        'cavalier_id',
        'cheval_id',
    ];

    public function championnat(): BelongsTo
    {
        return $this->belongsTo(Championnat::class);
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
