<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Championnat extends Model
{
    protected $fillable = [
        'concours_id',
        'nom',
        'epreuve1_id',
        'epreuve2_id',
    ];

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function epreuve1(): BelongsTo
    {
        return $this->belongsTo(Epreuve::class, 'epreuve1_id');
    }

    public function epreuve2(): BelongsTo
    {
        return $this->belongsTo(Epreuve::class, 'epreuve2_id');
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(ChampionnatExclusion::class);
    }

    /**
     * Couples cavalier+cheval engagés dans les DEUX épreuves.
     */
    public function participants(): Collection
    {
        return DB::table('engagements as e1')
            ->join('engagements as e2', function ($join) {
                $join->on('e1.cavalier_id', '=', 'e2.cavalier_id')
                    ->on('e1.cheval_id', '=', 'e2.cheval_id');
            })
            ->join('cavaliers', 'cavaliers.id', '=', 'e1.cavalier_id')
            ->join('chevaux', 'chevaux.id', '=', 'e1.cheval_id')
            ->where('e1.epreuve_id', $this->epreuve1_id)
            ->where('e2.epreuve_id', $this->epreuve2_id)
            ->select(
                'cavaliers.id as cavalier_id',
                'cavaliers.nom as cavalier_nom',
                'cavaliers.prenom as cavalier_prenom',
                'cavaliers.club',
                'chevaux.id as cheval_id',
                'chevaux.nom as cheval_nom',
            )
            ->orderBy('cavaliers.nom')
            ->orderBy('cavaliers.prenom')
            ->get();
    }
}
