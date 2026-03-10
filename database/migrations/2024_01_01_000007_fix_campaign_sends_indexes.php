<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique constraint to prevent duplicate sends to same contact in same campaign
        Schema::table('campaign_sends', function (Blueprint $table) {
            $table->unique(['campaign_id', 'contact_id'], 'campaign_sends_campaign_contact_unique');
        });

        // Add index for status queries (stats aggregation)
        Schema::table('campaign_sends', function (Blueprint $table) {
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('campaign_sends', function (Blueprint $table) {
            $table->dropUnique('campaign_sends_campaign_contact_unique');
            $table->dropIndex(['campaign_id', 'status']);
        });
    }
};
