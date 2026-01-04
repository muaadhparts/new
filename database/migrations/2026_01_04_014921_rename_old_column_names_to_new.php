<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable strict mode temporarily
        DB::statement('SET SESSION sql_mode = ""');

        // Rename comment_id to buyer_note_id in note_responses table
        DB::statement('ALTER TABLE note_responses CHANGE comment_id buyer_note_id INT UNSIGNED NULL');

        // Rename conversation_id to chat_thread_id in chat_entries table
        DB::statement('ALTER TABLE chat_entries CHANGE conversation_id chat_thread_id INT UNSIGNED NULL');

        // Rename conversation_id to chat_thread_id in catalog_events table
        DB::statement('ALTER TABLE catalog_events CHANGE conversation_id chat_thread_id INT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE note_responses CHANGE buyer_note_id comment_id INT UNSIGNED NULL');
        DB::statement('ALTER TABLE chat_entries CHANGE chat_thread_id conversation_id INT UNSIGNED NULL');
        DB::statement('ALTER TABLE catalog_events CHANGE chat_thread_id conversation_id INT UNSIGNED NULL');
    }
};
