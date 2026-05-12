<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->foreignId('second_engagement_id')->nullable()->after('engagement_id')
                ->constrained('engagements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Engagement::class, 'second_engagement_id');
            $table->dropColumn('second_engagement_id');
        });
    }
};
