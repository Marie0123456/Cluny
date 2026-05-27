<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactureClientFait extends Model
{
    protected $fillable = ['concours_id', 'client_facturation_id', 'section', 'item_id', 'fait', 'fait_par_id', 'fait_le'];

    protected function casts(): array
    {
        return [
            'fait'    => 'boolean',
            'fait_le' => 'datetime',
        ];
    }

    public function faitPar()
    {
        return $this->belongsTo(User::class, 'fait_par_id');
    }
}
