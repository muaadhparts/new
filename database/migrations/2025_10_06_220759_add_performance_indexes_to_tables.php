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
        // ✅ إضافة indexes مهمة للأداء (استخدم SQL مباشر لتجنب timeout)

        // 1. Index على illustrations (section_id, code) - استعلام متكرر
        DB::statement('CREATE INDEX IF NOT EXISTS idx_illustrations_section_code ON illustrations(section_id, code)');

        // 2. Index على callouts (illustration_id, callout_type) - لجلب callouts
        DB::statement('CREATE INDEX IF NOT EXISTS idx_callouts_illustration_type ON callouts(illustration_id, callout_type)');

        // 3. Index على newcategories (level, full_code) - للبحث السريع
        DB::statement('CREATE INDEX IF NOT EXISTS idx_newcategories_level_fullcode ON newcategories(level, full_code(50))');

        // 4. Index على sections (category_id, catalog_id)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sections_category_catalog ON sections(category_id, catalog_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_illustrations_section_code ON illustrations');
        DB::statement('DROP INDEX IF EXISTS idx_callouts_illustration_type ON callouts');
        DB::statement('DROP INDEX IF EXISTS idx_newcategories_level_fullcode ON newcategories');
        DB::statement('DROP INDEX IF EXISTS idx_sections_category_catalog ON sections');
    }
};
