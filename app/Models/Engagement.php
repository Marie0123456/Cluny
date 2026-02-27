<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Engagement extends Model
{
    use HasFactory;

    protected $fillable = [
        'epreuve_id',
        'cavalier_id',
        'cheval_id',
        'numero_depart',
        'role_cavalier',
        'dept_groom',
        'role_cheval',
        'is_invitation',
        'is_non_partant',
    ];

    protected function casts(): array
    {
        return [
            'is_invitation' => 'boolean',
            'is_non_partant' => 'boolean',
        ];
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

    public function modifications(): HasMany
    {
        return $this->hasMany(Modification::class);
    }
}
