<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * SkuAlternative - جدول البدائل
 *
 * Table: sku_alternatives
 * Columns: id, part_number, group_id, created_at, updated_at
 *
 * البدائل مرتبطة عبر group_id:
 * - كل الأصناف بنفس group_id هي بدائل لبعضها البعض
 * - إذا A في group_id=100 و B في group_id=100، فـ A بديل لـ B و B بديل لـ A
 */
class SkuAlternative extends Model
{
    use HasFactory;

    protected $table = 'sku_alternatives';
    protected $fillable = ['part_number', 'group_id'];

    /**
     * CatalogItem المرتبط بهذا الـ part_number
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'part_number', 'part_number');
    }

    /**
     * جلب البدائل في نفس المجموعة (باستثناء نفسه)
     */
    public function alternatives(): HasMany
    {
        return $this->hasMany(self::class, 'group_id', 'group_id')
            ->where('id', '!=', $this->id);
    }

    /**
     * جلب كل الأصناف في نفس المجموعة (بما فيها نفسه)
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(self::class, 'group_id', 'group_id');
    }

    /**
     * عدد البدائل في المجموعة
     */
    public function getAlternativesCountAttribute(): int
    {
        return self::where('group_id', $this->group_id)
            ->where('id', '!=', $this->id)
            ->count();
    }

    /**
     * جلب أو إنشاء group_id جديد
     */
    public static function getNextGroupId(): int
    {
        return (self::max('group_id') ?? 0) + 1;
    }

    /**
     * البحث عن صنف بواسطة part_number
     */
    public static function findByPartNumber(string $partNumber): ?self
    {
        return self::where('part_number', $partNumber)->first();
    }

    /**
     * إضافة بديل لصنف معين
     * يضع كلا الصنفين في نفس group_id
     */
    public static function addAlternative(string $mainPartNumber, string $alternativePartNumber): bool
    {
        $main = self::findByPartNumber($mainPartNumber);
        $alternative = self::findByPartNumber($alternativePartNumber);

        // إذا الرئيسي غير موجود، نضيفه مع group_id جديد
        if (!$main) {
            $main = self::create([
                'part_number' => $mainPartNumber,
                'group_id' => self::getNextGroupId(),
            ]);
        }

        // إذا البديل غير موجود، نضيفه في نفس المجموعة
        if (!$alternative) {
            self::create([
                'part_number' => $alternativePartNumber,
                'group_id' => $main->group_id,
            ]);
            return true;
        }

        // إذا البديل موجود ولكن في مجموعة مختلفة، ننقله لمجموعة الرئيسي
        if ($alternative->group_id !== $main->group_id) {
            // ننقل كل أعضاء مجموعة البديل إلى مجموعة الرئيسي
            self::where('group_id', $alternative->group_id)
                ->update(['group_id' => $main->group_id]);
            return true;
        }

        // البديل موجود بالفعل في نفس المجموعة
        return false;
    }

    /**
     * إزالة بديل من مجموعة (ينقله لمجموعة خاصة به)
     */
    public static function removeAlternative(string $partNumber): bool
    {
        $item = self::findByPartNumber($partNumber);
        if (!$item) {
            return false;
        }

        // ننقله لمجموعة جديدة خاصة به
        $item->update(['group_id' => self::getNextGroupId()]);
        return true;
    }

    /**
     * جلب البدائل لصنف معين
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
