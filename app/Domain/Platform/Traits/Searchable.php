<?php

namespace App\Domain\Platform\Traits;

/**
 * Searchable Trait
 *
 * Provides search functionality for models.
 */
trait Searchable
{
    /**
     * Scope to search
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        $columns = $this->getSearchableColumns();

        return $query->where(function ($q) use ($term, $columns) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                if (str_contains($column, '.')) {
                    // Relationship column
                    [$relation, $field] = explode('.', $column, 2);
                    $q->{$method . 'Has'}($relation, function ($subQuery) use ($field, $term) {
                        $subQuery->where($field, 'LIKE', "%{$term}%");
                    });
                } else {
                    $q->{$method}($column, 'LIKE', "%{$term}%");
                }
            }
        });
    }

    /**
     * Scope to search exact
     */
    public function scopeSearchExact($query, ?string $term, ?string $column = null)
    {
        if (empty($term)) {
            return $query;
        }

        $column = $column ?? $this->getSearchableColumns()[0] ?? 'name';

        return $query->where($column, $term);
    }

    /**
     * Get searchable columns
     */
    public function getSearchableColumns(): array
    {
        return $this->searchable ?? ['name'];
    }

    /**
     * Set searchable columns
     */
    public function setSearchableColumns(array $columns): self
    {
        $this->searchable = $columns;
        return $this;
    }

    /**
     * Scope to search with relevance
     */
    public function scopeSearchWithRelevance($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        $columns = $this->getSearchableColumns();
        $selects = [];

        foreach ($columns as $column) {
            if (!str_contains($column, '.')) {
                $selects[] = "CASE WHEN {$column} = '{$term}' THEN 3
                              WHEN {$column} LIKE '{$term}%' THEN 2
                              WHEN {$column} LIKE '%{$term}%' THEN 1
                              ELSE 0 END";
            }
        }

        if (!empty($selects)) {
            $relevance = '(' . implode(' + ', $selects) . ') as relevance';
            $query->selectRaw($query->getModel()->getTable() . '.*, ' . $relevance)
                ->orderByDesc('relevance');
        }

        return $this->scopeSearch($query, $term);
    }
}
