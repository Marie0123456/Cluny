<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facture_commentaires', function (Blueprint $table) {
            $table->foreignId('facture_faite_par_id')->nullable()->constrained('users')->nullOnDelete()->after('facture_faite');
            $table->timestamp('facture_faite_le')->nullable()->after('facture_faite_par_id');
        });
    }

    public function down(): void
    {
        Schema::table('facture_commentaires', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facture_faite_par_id');
            $table->dropColumn('facture_faite_le');
        });
    }
};
