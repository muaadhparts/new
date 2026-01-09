<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix Double-Encoded Cart Data Migration
 *
 * This migration fixes cart data that was incorrectly double-encoded.
 *
 * PROBLEM:
 * ========
 * Some cart data in the purchases table was stored as double-encoded JSON:
 * - First encode: array -> JSON string '{"items":...}'
 * - Second encode: JSON string -> escaped string '"{\"items\":...}"'
 *
 * This happened because controllers were manually calling json_encode()
 * before saving to a column that had an 'array' cast (which also encodes).
 *
 * SOLUTION:
 * =========
 * This migration detects double-encoded data (starts with '"' when decoded)
 * and fixes it by decoding twice.
 *
 * After this migration, all cart data will be properly stored as JSON,
 * and the 'array' cast will correctly decode it to arrays.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->fixDoubleEncodedCarts('purchases');
        $this->fixDoubleEncodedCarts('merchant_purchases');
    }

    public function down(): void
    {
        // Cannot reverse - data is now correct
    }

    private function fixDoubleEncodedCarts(string $table): void
    {
        $fixedCount = 0;
        $errorCount = 0;

        // Process records in chunks to handle large datasets
        DB::table($table)
            ->whereNotNull('cart')
            ->where('cart', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($records) use ($table, &$fixedCount, &$errorCount) {
                foreach ($records as $record) {
                    try {
                        $cart = $record->cart;

                        // Check if it's double-encoded
                        // Double-encoded JSON looks like: "{\"key\":\"value\"}"
                        // When decoded once, it returns a string (not array)
                        $decoded = json_decode($cart, true);

                        if (is_string($decoded)) {
                            // It's double-encoded - decode again
                            $actualData = json_decode($decoded, true);

                            if (is_array($actualData)) {
                                // Successfully decoded - update the record
                                DB::table($table)
                                    ->where('id', $record->id)
                                    ->update(['cart' => json_encode($actualData)]);

                                $fixedCount++;
                                Log::info("Fixed double-encoded cart for {$table} #{$record->id}");
                            } else {
                                $errorCount++;
                                Log::warning("Could not parse double-decoded cart for {$table} #{$record->id}");
                            }
                        }
                        // If decoded is already an array, it's correctly encoded - skip
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error("Error fixing cart for {$table} #{$record->id}: " . $e->getMessage());
                    }
                }
            });

        Log::info("Cart data fix for {$table} completed: {$fixedCount} fixed, {$errorCount} errors");
    }
};
