<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commande_retraits', function (Blueprint $table) {
            $table->text('note_client')->nullable()->after('emplacement_boxes');
        });
    }

    public function down(): void
    {
        Schema::table('commande_retraits', function (Blueprint $table) {
            $table->dropColumn('note_client');
        });
    }
};
