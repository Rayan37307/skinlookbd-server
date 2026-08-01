<?php

namespace App\Providers\Filament;

use App\Models\SiteMedia;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Throwable;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('SkinLookBD')
            ->brandLogo(fn () => self::resolveBrandLogo())
            ->darkModeBrandLogo(fn () => self::resolveBrandLogo())
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            ->font('Instrument Sans')
            ->darkMode()
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->colors([
                'primary' => Color::hex('#ff0055'),
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'info' => Color::Sky,
            ])
            ->navigationGroups([
                NavigationGroup::make('Orders')
                    ->icon(Heroicon::OutlinedShoppingBag),
                NavigationGroup::make('Customers')
                    ->icon(Heroicon::OutlinedUsers),
                NavigationGroup::make('Catalog')
                    ->icon(Heroicon::OutlinedCube),
                NavigationGroup::make('Promotions')
                    ->icon(Heroicon::OutlinedTag),
                NavigationGroup::make('Reviews')
                    ->icon(Heroicon::OutlinedStar),
                NavigationGroup::make('Appearance')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->collapsed(),
                NavigationGroup::make('Staff')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Mirrors the storefront logo (managed via the "Appearance / Media" settings page) so the
     * admin panel branding always matches whatever the storefront currently shows. Falls back
     * to the text brand name when nothing's been uploaded yet, or the database isn't reachable
     * (e.g. mid-deploy, before migrations have run).
     */
    private static function resolveBrandLogo(): ?string
    {
        try {
            $logo = SiteMedia::where('key', 'logo')->where('is_active', true)->first();
        } catch (Throwable) {
            return null;
        }

        if (! $logo?->image_path) {
            return null;
        }

        return $logo->hasExternalImageUrl()
            ? $logo->image_path
            : Storage::disk('public')->url($logo->image_path);
    }
}
