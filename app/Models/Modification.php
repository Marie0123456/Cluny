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
        'ancien_cavalier_id',
        'nouveau_cavalier_id',
        'linked_modification_id',
        'statut',
        'prix',
        'pf',
        'type_compte',
        'numero_compte',
        'is_gn',
        'paiement_cb',
        'paiement_especes',
        'paiement_cheque',
        'paiement_internet',
        'paiement_virement',
        'numero_cheque',
        'jour_paiement',
        'facture',
        'client_facturation_id',
        'created_by',
        'modified_by',
        'done_by',
        'done_at',
        'source_import',
    ];

    protected function casts(): array
    {
        return [
            'type' => ModificationType::class,
            'statut' => ModificationStatut::class,
            'prix' => 'decimal:2',
            'pf' => 'decimal:2',
            'is_gn' => 'boolean',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
            'jour_paiement' => 'date',
            'facture' => 'boolean',
            'done_at' => 'datetime',
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

    public function ancienCavalier(): BelongsTo
    {
        return $this->belongsTo(Cavalier::class, 'ancien_cavalier_id');
    }

    public function nouveauCavalier(): BelongsTo
    {
        return $this->belongsTo(Cavalier::class, 'nouveau_cavalier_id');
    }

    public function linkedModification(): BelongsTo
    {
        return $this->belongsTo(Modification::class, 'linked_modification_id');
    }

    public function clientFacturation(): BelongsTo
    {
        return $this->belongsTo(ClientFacturation::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function doneByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
