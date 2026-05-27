<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaisseFait extends Model
{
    protected $fillable = ['concours_id', 'section', 'groupe_key', 'fait', 'fait_par_id', 'fait_le'];

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
