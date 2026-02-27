<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->decimal('prix', 8, 2)->nullable()->after('statut');
            $table->decimal('pf', 8, 2)->nullable()->after('prix');
            $table->string('type_compte')->nullable()->after('pf');
            $table->string('numero_compte')->nullable()->after('type_compte');
            $table->boolean('is_gn')->default(false)->after('numero_compte');
            $table->boolean('paiement_cb')->default(false)->after('is_gn');
            $table->boolean('paiement_especes')->default(false)->after('paiement_cb');
            $table->boolean('paiement_cheque')->default(false)->after('paiement_especes');
            $table->string('numero_cheque')->nullable()->after('paiement_cheque');
            $table->date('jour_paiement')->nullable()->after('numero_cheque');
            $table->boolean('facture')->default(false)->after('jour_paiement');
            $table->foreignId('client_facturation_id')->nullable()->after('facture')
                ->constrained('clients_facturation')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->dropForeign(['client_facturation_id']);
            $table->dropColumn([
                'prix', 'pf', 'type_compte', 'numero_compte', 'is_gn',
                'paiement_cb', 'paiement_especes', 'paiement_cheque',
                'numero_cheque', 'jour_paiement', 'facture', 'client_facturation_id',
            ]);
        });
    }
};
