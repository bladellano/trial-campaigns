<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique index on email to prevent duplicate emails
        Schema::table('contacts', function (Blueprint $table) {
            $table->unique('email');
        });

        // Add index on status for filtering queries
        Schema::table('contacts', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropIndex(['status']);
        });
    }
};
