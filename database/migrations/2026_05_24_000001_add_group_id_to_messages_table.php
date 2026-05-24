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
            // Make receiver_id nullable for group messages
            $table->unsignedBigInteger('receiver_id')->nullable()->change();
            
            // Add group_id
            $table->foreignId('group_id')->nullable()->after('receiver_id')->constrained('groups')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
            $table->unsignedBigInteger('receiver_id')->nullable(false)->change();
        });
    }
};
