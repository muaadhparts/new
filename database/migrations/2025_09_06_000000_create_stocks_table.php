<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
    Schema::create('stocks', function (Blueprint $table) {
        $table->id();
        $table->string('part_number')->index();
        $table->unsignedInteger('branch_id')->index();
        $table->string('location')->nullable()->index();
        $table->integer('qty')->default(0);
        $table->decimal('sell_price', 18, 4)->nullable();
        $table->decimal('comp_cost', 18, 4)->nullable();
        $table->decimal('cost_price', 18, 4)->nullable();
        $table->timestamps();

        $table->unique(['part_number', 'location'], 'stocks_unique_item_location');
    });

    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
