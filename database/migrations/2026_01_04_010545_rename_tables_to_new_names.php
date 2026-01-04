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
        Schema::rename('affliate_bonuses', 'referral_commissions');
        Schema::rename('arrival_sections', 'featured_promos');
        Schema::rename('attribute_options', 'spec_values');
        Schema::rename('blog_categories', 'article_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('referral_commissions', 'affliate_bonuses');
        Schema::rename('featured_promos', 'arrival_sections');
        Schema::rename('spec_values', 'attribute_options');
        Schema::rename('article_types', 'blog_categories');
    }
};
