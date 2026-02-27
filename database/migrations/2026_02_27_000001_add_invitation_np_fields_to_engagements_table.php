<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engagements', function (Blueprint $table) {
            $table->boolean('is_invitation')->default(false)->after('role_cheval');
            $table->boolean('is_non_partant')->default(false)->after('is_invitation');
        });
    }

    public function down(): void
    {
        Schema::table('engagements', function (Blueprint $table) {
            $table->dropColumn(['is_invitation', 'is_non_partant']);
        });
    }
};
