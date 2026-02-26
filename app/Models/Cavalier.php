<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cavalier extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'num_licence',
        'club',
        'cre',
        'departement',
        'num_departement',
    ];

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
}
