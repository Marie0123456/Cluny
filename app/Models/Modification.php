<?php

namespace App\Models;

use App\Enums\ModificationStatut;
use App\Enums\ModificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modification extends Model
{
    use HasFactory;

    protected $fillable = [
        'engagement_id',
        'concours_id',
        'type',
        'description',
        'ancien_cheval_id',
        'nouveau_cheval_id',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'type' => ModificationType::class,
            'statut' => ModificationStatut::class,
        ];
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function ancienCheval(): BelongsTo
    {
        return $this->belongsTo(Cheval::class, 'ancien_cheval_id');
    }

    public function nouveauCheval(): BelongsTo
    {
        return $this->belongsTo(Cheval::class, 'nouveau_cheval_id');
    }
}
