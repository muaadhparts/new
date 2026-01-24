<?php

namespace App\Domain\Identity\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User Repository
 *
 * Repository for user data access.
 */
class UserRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Find by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findFirstBy('email', $email);
    }

    /**
     * Find by phone.
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->findFirstBy('phone', $phone);
    }

    /**
     * Get merchants.
     */
    public function getMerchants(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('is_merchant', 1)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get active merchants.
     */
    public function getActiveMerchants(): Collection
    {
        return $this->query()
            ->where('is_merchant', 1)
            ->where('status', 1)
            ->get();
    }

    /**
     * Get customers.
     */
    public function getCustomers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('is_merchant', 0)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unverified users.
     */
    public function getUnverified(): Collection
    {
        return $this->query()
            ->whereNull('email_verified_at')
            ->get();
    }

    /**
     * Get recently registered users.
     */
    public function getRecentlyRegistered(int $days = 7): Collection
    {
        return $this->query()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Search users.
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->paginate($perPage);
    }
}
