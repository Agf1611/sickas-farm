@if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
    <div class="sickas-topbar-theme-switcher" aria-label="Pengaturan tema tampilan">
        <span class="sickas-theme-label">Tema</span>
        <x-filament-panels::theme-switcher />
    </div>
@endif
