<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * SkuAlternative Model - Alternative parts grouping
 *
 * Domain: Catalog
 * Table: sku_alternatives
 *
 * Alternatives are linked via group_id:
 * - All items with the same group_id are alternatives to each other
 * - If A is in group_id=100 and B is in group_id=100, A is alternative to B and vice versa
 *
 * @property int $id
 * @property string $part_number
 * @property int $group_id
 */
class SkuAlternative extends Model
{
    use HasFactory;

    protected $table = 'sku_alternatives';
    protected $fillable = ['part_number', 'group_id'];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * CatalogItem linked to this part_number
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'part_number', 'part_number');
    }

    /**
     * Get alternatives in the same group (excluding self)
     */
    public function alternatives(): HasMany
    {
        return $this->hasMany(self::class, 'group_id', 'group_id')
            ->where('id', '!=', $this->id);
    }

    /**
     * Get all members in the same group (including self)
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(self::class, 'group_id', 'group_id');
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Count of alternatives in the group
     */
    public function getAlternativesCountAttribute(): int
    {
        return self::where('group_id', $this->group_id)
            ->where('id', '!=', $this->id)
            ->count();
    }

    // =========================================================
    // STATIC METHODS
    // =========================================================

    /**
     * Get the next available group_id
     */
    public static function getNextGroupId(): int
    {
        return (self::max('group_id') ?? 0) + 1;
    }

    /**
     * Find by part_number
     */
    public static function findByPartNumber(string $partNumber): ?self
    {
        return self::where('part_number', $partNumber)->first();
    }

    /**
     * Add an alternative to an item
     * Places both items in the same group_id
     */
    public static function addAlternative(string $mainPartNumber, string $alternativePartNumber): bool
    {
        $main = self::findByPartNumber($mainPartNumber);
        $alternative = self::findByPartNumber($alternativePartNumber);

        // If main doesn't exist, add it with new group_id
        if (!$main) {
            $main = self::create([
                'part_number' => $mainPartNumber,
                'group_id' => self::getNextGroupId(),
            ]);
        }

        // If alternative doesn't exist, add it to same group
        if (!$alternative) {
            self::create([
                'part_number' => $alternativePartNumber,
                'group_id' => $main->group_id,
            ]);
            return true;
        }

        // If alternative exists but in different group, move it to main's group
        if ($alternative->group_id !== $main->group_id) {
            // Move all members of alternative's group to main's group
            self::where('group_id', $alternative->group_id)
                ->update(['group_id' => $main->group_id]);
            return true;
        }

        // Alternative already exists in same group
        return false;
    }

    /**
     * Remove an alternative from its group (moves it to its own group)
     */
    public static function removeAlternative(string $partNumber): bool
    {
        $item = self::findByPartNumber($partNumber);
        if (!$item) {
            return false;
        }

        // Move to new group
        $item->update(['group_id' => self::getNextGroupId()]);
        return true;
    }

    /**
     * Get alternatives for a specific part_number
     */
    public static function getAlternativesFor(string $partNumber): array
    {
        $main = self::findByPartNumber($partNumber);
        if (!$main) {
            return [];
        }

        return self::where('group_id', $main->group_id)
            ->where('part_number', '<>', $partNumber)
            ->with('catalogItem')
            ->get()
            ->toArray();
    }
}
