<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique constraint to prevent duplicate contact in same list
        Schema::table('contact_contact_list', function (Blueprint $table) {
            $table->unique(['contact_id', 'contact_list_id'], 'contact_list_contact_unique');
        });

        // Add indexes for relationship queries
        Schema::table('contact_contact_list', function (Blueprint $table) {
            $table->index('contact_list_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_contact_list', function (Blueprint $table) {
            $table->dropUnique('contact_list_contact_unique');
            $table->dropIndex(['contact_list_id']);
        });
    }
};
