<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A read-only, database-view-backed model unifying registered customers (users with the
 * `customer` role) and guest customers (identified by the `recipient_phone` on their guest
 * orders). Backed by the `customers` view — see the `create_customers_view` migration.
 */
class Customer extends Model
{
    protected $table = 'customers';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'orders_count' => 'integer',
            'lifetime_value' => 'integer',
        ];
    }

    public function isGuest(): bool
    {
        return $this->type === 'guest';
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->isGuest()
            ? $this->hasMany(Order::class, 'recipient_phone', 'phone')->whereNull('user_id')
            : $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    /**
     * Guests never get a persisted Address row — their shipping details only ever exist as a
     * snapshot on each order — so this only ever returns rows for registered customers.
     *
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id', 'user_id');
    }
}
