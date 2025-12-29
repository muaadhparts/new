<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create support_threads and support_messages tables.
     * Safe approach: Create new tables, migrate data, rename old with _old suffix
     */
    public function up(): void
    {
        // Step 1: Create support_threads table
        Schema::create('support_threads', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 191);
            $table->integer('user_id');
            $table->text('message');
            $table->enum('type', ['Ticket', 'Dispute'])->nullable();
            $table->text('order_number')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
        });

        // Step 2: Create support_messages table
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->text('message');
            $table->integer('user_id')->nullable();
            $table->timestamps();

            $table->index('thread_id');
            $table->index('user_id');
        });

        // Step 3: Migrate data from old tables to new tables
        DB::statement('
            INSERT INTO support_threads (id, subject, user_id, message, type, order_number, created_at, updated_at)
            SELECT id, subject, user_id, message, type, order_number, created_at, updated_at
            FROM admin_user_conversations
        ');

        DB::statement('
            INSERT INTO support_messages (id, thread_id, message, user_id, created_at, updated_at)
            SELECT id, conversation_id, message, user_id, created_at, updated_at
            FROM admin_user_messages
        ');

        // Step 4: Rename old tables with _old suffix (NEVER DELETE!)
        Schema::rename('admin_user_conversations', 'admin_user_conversations_old');
        Schema::rename('admin_user_messages', 'admin_user_messages_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old tables from _old suffix
        Schema::rename('admin_user_conversations_old', 'admin_user_conversations');
        Schema::rename('admin_user_messages_old', 'admin_user_messages');

        // Drop new tables (safe - data is in restored tables)
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_threads');
    }
};
