<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->boolean('caisse_facture_faite')->default(false)->after('ffe_password');
        });
    }

    public function down(): void
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->dropColumn('caisse_facture_faite');
        });
    }
};
