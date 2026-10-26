<?php

namespace App\Services;

use App\Models\ShippingZone;
use Illuminate\Support\Facades\DB;

/**
 * Reads and writes the delivery zones shown on the "Delivery" settings page. Kept out of the
 * Filament page so the persistence rules (zone upsert, rate upsert, removal of dropped zones)
 * can be exercised directly by tests.
 */
class ShippingSettingsService
{
    /**
     * Rows in the shape the settings form expects. When nothing has been configured yet, the
     * built-in tiers are returned as unsaved rows so the admin starts from the current pricing.
     *
     * @return array<int, array{id: ?int, name: string, areas: array<int, string>, charge: int, eta_days: ?int, free_delivery_threshold: ?int}>
     */
    public function rows(): array
    {
        $zones = ShippingZone::with('rate')->orderBy('id')->get();

        if ($zones->isEmpty()) {
            return array_map(fn (array $zone): array => [
                'id' => null,
                'name' => $zone['name'],
                'areas' => $zone['areas'],
                'charge' => $zone['charge'],
                'eta_days' => $zone['eta_days'],
                'free_delivery_threshold' => null,
            ], ShippingService::DEFAULT_ZONES);
        }

        return $zones->map(fn (ShippingZone $zone): array => [
            'id' => $zone->id,
            'name' => $zone->name,
            'areas' => $zone->areas ?? [],
            'charge' => $zone->rate?->charge ?? 0,
            'eta_days' => $zone->rate?->eta_days,
            'free_delivery_threshold' => $zone->rate?->free_delivery_threshold,
        ])->all();
    }

    /**
     * Replaces the configured zones with the given rows: existing zones (matched by `id`) are
     * updated, rows without an id create a new zone, and zones missing from `$rows` are deleted.
     *
     * @param  array<int, array{id?: int|null, name: string, areas?: array<int, string>, charge: int|string, eta_days: int|string|null, free_delivery_threshold: int|string|null}>  $rows
     */
    public function save(array $rows): void
    {
        DB::transaction(function () use ($rows): void {
            $keptIds = [];

            foreach ($rows as $row) {
                $zone = ShippingZone::find($row['id'] ?? null) ?? new ShippingZone;
                $zone->fill([
                    'name' => $row['name'],
                    'areas' => array_values($row['areas'] ?? []),
                ])->save();

                $zone->rate()->updateOrCreate([], [
                    'charge' => (int) $row['charge'],
                    'eta_days' => filled($row['eta_days'] ?? null) ? (int) $row['eta_days'] : null,
                    'free_delivery_threshold' => filled($row['free_delivery_threshold'] ?? null)
                        ? (int) $row['free_delivery_threshold']
                        : null,
                ]);

                $keptIds[] = $zone->id;
            }

            ShippingZone::whereNotIn('id', $keptIds)->delete();
        });
    }
}
