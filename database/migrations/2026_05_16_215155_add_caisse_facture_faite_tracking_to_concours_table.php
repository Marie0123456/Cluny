<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->foreignId('caisse_facture_faite_par_id')->nullable()->constrained('users')->nullOnDelete()->after('caisse_facture_faite');
            $table->timestamp('caisse_facture_faite_le')->nullable()->after('caisse_facture_faite_par_id');
        });
    }

    public function down(): void
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caisse_facture_faite_par_id');
            $table->dropColumn('caisse_facture_faite_le');
        });
    }
};
