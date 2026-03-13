<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vente extends Model
{
    use HasFactory;

    protected $fillable = [
        'concours_id',
        'nom_client',
        'jour_paiement',
        'paiement_cb',
        'paiement_especes',
        'paiement_cheque',
        'paiement_internet',
        'paiement_virement',
        'numero_cheque',
        'facture',
        'client_facturation_id',
        'commentaire',
        'total_ttc',
    ];

    protected function casts(): array
    {
        return [
            'jour_paiement' => 'date',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
            'facture' => 'boolean',
            'total_ttc' => 'decimal:2',
        ];
    }

    public function concours(): BelongsTo
    {
        return $this->belongsTo(Concours::class);
    }

    public function clientFacturation(): BelongsTo
    {
        return $this->belongsTo(ClientFacturation::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(VenteLigne::class);
    }

    public function recalculerTotal(): void
    {
        $this->update([
            'total_ttc' => $this->lignes()->sum('total_ttc'),
        ]);
    }
}
