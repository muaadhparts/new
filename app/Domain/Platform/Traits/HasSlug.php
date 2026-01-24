<?php

namespace App\Domain\Platform\Traits;

use Illuminate\Support\Str;

/**
 * Has Slug Trait
 *
 * Provides automatic slug generation for models.
 */
trait HasSlug
{
    /**
     * Boot the trait
     */
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getSlugColumn()})) {
                $model->{$model->getSlugColumn()} = $model->generateSlug();
            }
        });

        static::updating(function ($model) {
            if ($model->shouldRegenerateSlug()) {
                $model->{$model->getSlugColumn()} = $model->generateSlug();
            }
        });
    }

    /**
     * Generate a unique slug
     */
    public function generateSlug(): string
    {
        $slug = Str::slug($this->{$this->getSlugSourceColumn()});
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where($this->getSlugColumn(), $slug);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    /**
     * Get the slug column name
     */
    public function getSlugColumn(): string
    {
        return $this->slugColumn ?? 'slug';
    }

    /**
     * Get the source column for slug generation
     */
    public function getSlugSourceColumn(): string
    {
        return $this->slugSourceColumn ?? 'name';
    }

    /**
     * Check if slug should be regenerated on update
     */
    protected function shouldRegenerateSlug(): bool
    {
        return $this->isDirty($this->getSlugSourceColumn())
            && ($this->regenerateSlugOnUpdate ?? false);
    }

    /**
     * Get the route key name
     */
    public function getRouteKeyName(): string
    {
        return $this->getSlugColumn();
    }

    /**
     * Find by slug
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::where((new static)->getSlugColumn(), $slug)->first();
    }

    /**
     * Find by slug or fail
     */
    public static function findBySlugOrFail(string $slug): static
    {
        return static::where((new static)->getSlugColumn(), $slug)->firstOrFail();
    }
}
