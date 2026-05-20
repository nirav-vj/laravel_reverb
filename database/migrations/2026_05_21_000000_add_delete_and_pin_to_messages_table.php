<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_deleted_for_everyone')->default(false);
            $table->json('deleted_by')->nullable(); // Array of user IDs who deleted this message for themselves
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'is_deleted_for_everyone', 'deleted_by']);
        });
    }
};
