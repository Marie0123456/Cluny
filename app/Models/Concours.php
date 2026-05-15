<?php

namespace App\Models;

use App\Enums\Discipline;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Concours extends Model
{
    use HasFactory;

    protected $table = 'concours';

    protected $fillable = [
        'nom',
        'date_debut',
        'date_fin',
        'discipline',
        'type_ffe_sif',
        'type_ffe_compet',
        'grand_national',
        'ffe_numero_concours',
        'ffe_login',
        'ffe_password',
        'caisse_facture_faite',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'discipline' => Discipline::class,
            'type_ffe_sif' => 'boolean',
            'type_ffe_compet' => 'boolean',
            'grand_national' => 'boolean',
            'ffe_password' => 'encrypted',
            'caisse_facture_faite' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function epreuves(): HasMany
    {
        return $this->hasMany(Epreuve::class);
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(Modification::class);
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function engagements(): HasManyThrough
    {
        return $this->hasManyThrough(Engagement::class, Epreuve::class);
    }

    public function championnats(): HasMany
    {
        return $this->hasMany(Championnat::class);
    }
}
