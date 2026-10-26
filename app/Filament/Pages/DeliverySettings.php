<?php

namespace App\Filament\Pages;

use App\Services\ShippingService;
use App\Services\ShippingSettingsService;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DeliverySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Delivery';

    protected static ?string $title = 'Delivery Charges';

    protected string $view = 'filament.pages.delivery-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(ShippingSettingsService $settings): void
    {
        $this->form->fill(['zones' => $settings->rows()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery zones')
                    ->description('Each zone sets the delivery charge, ETA and optional free-delivery threshold for the cities it covers. If a city appears in more than one zone, the first zone wins.')
                    ->schema([
                        Repeater::make('zones')
                            ->hiddenLabel()
                            ->minItems(1)
                            ->addActionLabel('Add zone')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('areas')
                                    ->label('Cities')
                                    ->multiple()
                                    ->searchable()
                                    ->required()
                                    ->options(fn (): array => array_combine(
                                        ShippingService::validCities(),
                                        ShippingService::validCities(),
                                    )),
                                TextInput::make('charge')
                                    ->label('Delivery charge (BDT)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0),
                                TextInput::make('eta_days')
                                    ->label('ETA (days)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(60),
                                TextInput::make('free_delivery_threshold')
                                    ->label('Free delivery from (BDT)')
                                    ->helperText('Optional. When the cart subtotal (before coupons) reaches this amount, delivery is free. Leave blank to always charge.')
                                    ->numeric()
                                    ->minValue(0),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(ShippingSettingsService $settings): void
    {
        $state = $this->form->getState();

        $settings->save($state['zones'] ?? []);

        Notification::make()->title('Delivery charges updated')->success()->send();

        $this->mount($settings);
    }
}
