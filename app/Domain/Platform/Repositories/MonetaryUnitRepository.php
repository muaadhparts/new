<?php

namespace App\Domain\Platform\Repositories;

use App\Domain\Platform\Models\MonetaryUnit;
use Illuminate\Database\Eloquent\Collection;

/**
 * Monetary Unit Repository
 *
 * Repository for monetary unit (currency) data access.
 */
class MonetaryUnitRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return MonetaryUnit::class;
    }

    /**
     * Get the default monetary unit.
     */
    public function getDefault(): ?MonetaryUnit
    {
        return $this->findFirstBy('is_default', 1);
    }

    /**
     * Get all active monetary units.
     */
    public function getActive(): Collection
    {
        return $this->findBy('status', 1);
    }

    /**
     * Set default monetary unit.
     */
    public function setDefault(int $id): bool
    {
        // Remove default from all
        $this->query()->update(['is_default' => 0]);

        // Set new default
        return $this->update($id, ['is_default' => 1]);
    }

    /**
     * Find by code.
     */
    public function findByCode(string $code): ?MonetaryUnit
    {
        return $this->findFirstBy('code', strtoupper($code));
    }

    /**
     * Get monetary units for dropdown.
     */
    public function getForDropdown(): array
    {
        return $this->getActive()
            ->pluck('name', 'id')
            ->toArray();
    }
}
