<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Rename _old suffix columns that are still in use
 *
 * These columns are still used by the application but were marked with _old
 * This migration removes the _old suffix to match the code references
 */
return new class extends Migration
{
    /**
     * Columns to rename: old_name => new_name
     */
    private array $columnsToRename = [
        'loader_old' => 'loader',
        'withdraw_fee_old' => 'withdraw_fee',
        'withdraw_charge_old' => 'withdraw_charge',
        'mail_driver_old' => 'mail_driver',
        'mail_host_old' => 'mail_host',
        'mail_port_old' => 'mail_port',
        'mail_encryption_old' => 'mail_encryption',
        'mail_user_old' => 'mail_user',
        'mail_pass_old' => 'mail_pass',
        'from_email_old' => 'from_email',
        'from_name_old' => 'from_name',
        'is_buyer_note_old' => 'is_buyer_note',
        'is_popup_old' => 'is_popup',
        'popup_background_old' => 'popup_background',
        'user_image_old' => 'user_image',
        'merchant_color_old' => 'merchant_color',
        'maintain_text_old' => 'maintain_text',
    ];

    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            foreach ($this->columnsToRename as $oldName => $newName) {
                if (Schema::hasColumn('muaadhsettings', $oldName) && !Schema::hasColumn('muaadhsettings', $newName)) {
                    $table->renameColumn($oldName, $newName);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            foreach ($this->columnsToRename as $oldName => $newName) {
                if (Schema::hasColumn('muaadhsettings', $newName) && !Schema::hasColumn('muaadhsettings', $oldName)) {
                    $table->renameColumn($newName, $oldName);
                }
            }
        });
    }
};
