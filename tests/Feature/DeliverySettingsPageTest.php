<?php

use App\Filament\Pages\DeliverySettings;
use App\Models\ShippingZone;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function actingAsDeliverySettingsAdmin(): void
{
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    test()->actingAs($user);
    Filament::setCurrentPanel('admin');
}

test('the delivery settings page starts from the built-in tiers when nothing is configured', function () {
    actingAsDeliverySettingsAdmin();

    Livewire::test(DeliverySettings::class)
        ->assertOk()
        ->assertSet('data.zones', fn (array $zones): bool => count($zones) === 3);
});

test('saving the delivery settings page persists zones and their free delivery threshold', function () {
    actingAsDeliverySettingsAdmin();

    Livewire::test(DeliverySettings::class)
        ->set('data.zones', [
            [
                'id' => null,
                'name' => 'Dhaka Metro',
                'areas' => ['Dhaka'],
                'charge' => 60,
                'eta_days' => 2,
                'free_delivery_threshold' => 2000,
            ],
            [
                'id' => null,
                'name' => 'Rest of Bangladesh',
                'areas' => ['Sylhet'],
                'charge' => 150,
                'eta_days' => 5,
                'free_delivery_threshold' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(ShippingZone::count())->toBe(2);

    $dhaka = ShippingZone::where('name', 'Dhaka Metro')->firstOrFail();
    expect($dhaka->rate->charge)->toBe(60);
    expect($dhaka->rate->free_delivery_threshold)->toBe(2000);

    expect(ShippingZone::where('name', 'Rest of Bangladesh')->firstOrFail()->rate->free_delivery_threshold)
        ->toBeNull();
});
