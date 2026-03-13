<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->boolean('paiement_internet')->default(false)->after('paiement_cheque');
            $table->boolean('paiement_virement')->default(false)->after('paiement_internet');
        });

        Schema::table('modifications', function (Blueprint $table) {
            $table->boolean('paiement_internet')->default(false)->after('paiement_cheque');
            $table->boolean('paiement_virement')->default(false)->after('paiement_internet');
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn(['paiement_internet', 'paiement_virement']);
        });

        Schema::table('modifications', function (Blueprint $table) {
            $table->dropColumn(['paiement_internet', 'paiement_virement']);
        });
    }
};
