<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change scheduled_at from string to timestamp
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->change();
        });

        // Add composite index for scheduled campaigns query
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_at']);
            $table->string('scheduled_at')->nullable()->change();
        });
    }
};
