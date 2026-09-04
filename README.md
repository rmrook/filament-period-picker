# Filament Period Picker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rmrook/filament-period-picker.svg?style=flat-square)](https://packagist.org/packages/rmrook/filament-period-picker)
[![Total Downloads](https://img.shields.io/packagist/dt/rmrook/filament-period-picker.svg?style=flat-square)](https://packagist.org/packages/rmrook/filament-period-picker)

A reusable Filament form field for selecting a date range. It combines quick presets, two synchronized Filament date inputs, and a shared range calendar in one responsive picker.

The package ships its own lazy-loaded JavaScript, CSS, views, and translations for English, Dutch, French, Italian, German, Spanish, Greek, and Japanese. A consuming application does not need to add Tailwind `@source` directives or register assets manually.

## Requirements

- PHP 8.2 or newer
- Laravel 11.28, 12, or 13
- Filament 4 or 5

## Installation

Install the Composer package:

```bash
composer require rmrook/filament-period-picker
```

Register the plugin in every Filament panel where the field is used:

```php
use RMRook\FilamentPeriodPicker\PeriodPickerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(PeriodPickerPlugin::make());
}
```

Publish Filament's registered assets after installing or updating the package:

```bash
php artisan filament:assets
```

## Basic usage

```php
use RMRook\FilamentPeriodPicker\Forms\Components\PeriodPicker;

PeriodPicker::make('period')
    ->label('Periode')
    ->default([
        'start' => now()->startOfYear()->toDateString(),
        'end' => now()->endOfYear()->toDateString(),
    ]);
```

By default, the dehydrated value is an array containing ISO dates:

```php
[
    'start' => '2026-01-01',
    'end' => '2026-12-31',
]
```

## Options

```php
PeriodPicker::make('period')
    ->locale('nl')
    ->firstDayOfWeek(1)
    ->displayFormat('d M Y')
    ->format('Y-m-d')
    ->native(false)
    ->closeOnDateSelection()
    ->minDate('2025-01-01')
    ->maxDate('2027-12-31')
    ->presets([
        [
            'key' => 'campaign',
            'label' => 'Campagneperiode',
            'start' => '2026-09-01',
            'end' => '2026-10-15',
        ],
    ]);
```

`locale()`, `firstDayOfWeek()`, `displayFormat()`, `format()`, `native()`, `closeOnDateSelection()`, `minDate()`, `maxDate()`, and `presets()` also accept closures with Filament utility injection. Invalid preset ranges are ignored.

`displayFormat()` controls how both date inputs are displayed. When native inputs are enabled, the browser controls their visual format. `format()` controls the format of the hydrated and dehydrated `start` and `end` values; the picker continues to use ISO dates internally.

For any other `DatePicker` option, configure both embedded inputs together or target one input:

```php
use Filament\Forms\Components\DatePicker;

PeriodPicker::make('period')
    ->configureDatePickersUsing(
        fn (DatePicker $datePicker) => $datePicker
            ->placeholder('Choose a date')
            ->extraInputAttributes(['autocomplete' => 'off'])
    )
    ->configureStartDatePickerUsing(
        fn (DatePicker $datePicker) => $datePicker->label('Beginning')
    )
    ->configureEndDatePickerUsing(
        fn (DatePicker $datePicker) => $datePicker->label('Ending')
    );
```

The configurator closures may also inject the parent `PeriodPicker` as `$component`, the current picker type as `$type` (`start` or `end`), and the usual Filament utilities. The embedded fields always remain non-dehydrated because the parent field owns the final range value.

Without custom presets, the picker provides:

- This year and last year
- This quarter and last quarter
- This month and last month
- Last 12 months

## Translations

The picker follows the Laravel application locale and includes English (`en`), Dutch (`nl`), French (`fr`), Italian (`it`), German (`de`), Spanish (`es`), Greek (`el`), and Japanese (`ja`). To customize its copy, publish the package translations:

```bash
php artisan vendor:publish --tag=filament-period-picker-translations
```

## Testing

```bash
composer test
```

## License

MIT
