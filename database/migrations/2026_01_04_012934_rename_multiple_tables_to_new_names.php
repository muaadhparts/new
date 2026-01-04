<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('comments', 'buyer_notes');
        Schema::rename('conversations', 'chat_threads');
        Schema::rename('messages', 'chat_entries');
        Schema::rename('subscribers', 'mailing_list');
        Schema::rename('transactions', 'wallet_logs');
        Schema::rename('pages', 'static_content');
        Schema::rename('replies', 'note_responses');
        Schema::rename('reports', 'abuse_flags');
        Schema::rename('reviews', 'testimonials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('buyer_notes', 'comments');
        Schema::rename('chat_threads', 'conversations');
        Schema::rename('chat_entries', 'messages');
        Schema::rename('mailing_list', 'subscribers');
        Schema::rename('wallet_logs', 'transactions');
        Schema::rename('static_content', 'pages');
        Schema::rename('note_responses', 'replies');
        Schema::rename('abuse_flags', 'reports');
        Schema::rename('testimonials', 'reviews');
    }
};
