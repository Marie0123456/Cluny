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

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class, 'client_facturation_id');
    }
}
