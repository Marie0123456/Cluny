<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientFacturation extends Model
{
    use HasFactory;

    protected $table = 'clients_facturation';

    protected $fillable = [
        'nom',
        'telephone',
        'email',
        'adresse',
    ];

    /**
     * Case-insensitive updateOrCreate by nom.
     */
    public static function updateOrCreateByNom(string $nom, array $attributes = []): self
    {
        $client = static::where('nom', $nom)->first();

        if ($client) {
            $client->update(array_filter($attributes, fn ($v) => $v !== null));

            return $client;
        }

        return static::create(array_merge(['nom' => $nom], $attributes));
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class, 'client_facturation_id');
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(Modification::class, 'client_facturation_id');
    }
}
