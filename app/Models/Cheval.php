<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cheval extends Model
{
    use HasFactory;

    protected $table = 'chevaux';

    protected $fillable = [
        'nom',
        'num_sire',
        'age',
        'sexe',
        'robe',
        'race',
        'etat',
    ];

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }
}
