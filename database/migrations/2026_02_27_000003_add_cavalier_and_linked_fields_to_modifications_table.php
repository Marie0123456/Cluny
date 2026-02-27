<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->foreignId('ancien_cavalier_id')->nullable()->after('nouveau_cheval_id')
                ->constrained('cavaliers')->nullOnDelete();
            $table->foreignId('nouveau_cavalier_id')->nullable()->after('ancien_cavalier_id')
                ->constrained('cavaliers')->nullOnDelete();
            $table->foreignId('linked_modification_id')->nullable()->after('nouveau_cavalier_id')
                ->constrained('modifications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->dropForeign(['ancien_cavalier_id']);
            $table->dropForeign(['nouveau_cavalier_id']);
            $table->dropForeign(['linked_modification_id']);
            $table->dropColumn(['ancien_cavalier_id', 'nouveau_cavalier_id', 'linked_modification_id']);
        });
    }
};
