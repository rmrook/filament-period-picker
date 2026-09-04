# Filament Period Picker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rmrook/filament-period-picker.svg?style=flat-square)](https://packagist.org/packages/rmrook/filament-period-picker)
[![Total Downloads](https://img.shields.io/packagist/dt/rmrook/filament-period-picker.svg?style=flat-square)](https://packagist.org/packages/rmrook/filament-period-picker)

A reusable Filament form field for selecting a date range. It combines quick presets, two synchronized Filament date inputs, and a shared range calendar in one responsive picker.

![Filament Period Picker with quick selections, date inputs, and a two-month range calendar](docs/period-picker.png)

The package ships its own lazy-loaded JavaScript, CSS, views, and translations for English, Dutch, French, Italian, German, Spanish, Greek, and Japanese. A consuming application does not need to add Tailwind `@source` directives or register assets manually.

## What you can do

- Select a start and end date from two synchronized inputs or a shared two-month calendar.
- Use ready-made selections for years, quarters, months, and a rolling 12-month period.
- Add your own fixed or dynamically calculated presets, such as today, this week, the last 30 days, year-to-date, a campaign period, or a booking window.
- Restrict the selectable period with a minimum date, a maximum date, or both.
- Choose the display format separately from the format stored in the form state.
- Use native browser date inputs or Filament's date picker.
- Configure both embedded date inputs together or customize the start and end inputs separately.
- Resolve options from closures, including the usual Filament utility injection.
- Localize labels, month names, weekday names, and the first day of the week.
- Use the same picker on desktop and mobile; the panel adapts to smaller viewports.

The selection is only committed when the user clicks **Apply**. **Cancel** restores the previously applied range. While selecting an end date, the calendar previews the range and starting again with an earlier date resets the range from that date.

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

| Method | Purpose | Default |
| --- | --- | --- |
| `presets()` | Replace the quick date selections | Built-in presets |
| `minDate()` | Earliest selectable date | No limit |
| `maxDate()` | Latest selectable date | No limit |
| `displayFormat()` | Format shown in both date inputs | `d M Y` |
| `format()` | Format accepted from and returned to the form state | `Y-m-d` |
| `locale()` | Locale used by the inputs and calendar | Application locale |
| `firstDayOfWeek()` | First weekday, using Carbon weekday constants | Monday |
| `native()` | Use native browser date inputs | `false` |
| `closeOnDateSelection()` | Close an embedded date input after choosing a date | `true` |
| `configureDatePickersUsing()` | Configure both embedded `DatePicker` fields | None |
| `configureStartDatePickerUsing()` | Configure only the start input | None |
| `configureEndDatePickerUsing()` | Configure only the end input | None |

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

Calling `presets()` replaces these defaults. Pass an empty array when you want to show only the custom calendar and date inputs:

```php
PeriodPicker::make('period')
    ->presets([]);
```

## Date selection recipes

### Relative date presets

Presets may describe a single day by using the same date for `start` and `end`. Because presets can be returned from a closure, relative periods are recalculated instead of becoming stale.

```php
use Carbon\CarbonImmutable;
use RMRook\FilamentPeriodPicker\Forms\Components\PeriodPicker;

PeriodPicker::make('period')
    ->presets(function (): array {
        $today = CarbonImmutable::now(
            config('app.user_timezone', config('app.timezone')),
        )->startOfDay();

        return [
            [
                'key' => 'today',
                'label' => 'Today',
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
            ],
            [
                'key' => 'yesterday',
                'label' => 'Yesterday',
                'start' => $today->subDay()->toDateString(),
                'end' => $today->subDay()->toDateString(),
            ],
            [
                'key' => 'this_week',
                'label' => 'This week',
                'start' => $today->startOfWeek()->toDateString(),
                'end' => $today->endOfWeek()->toDateString(),
            ],
            [
                'key' => 'last_7_days',
                'label' => 'Last 7 days',
                'start' => $today->subDays(6)->toDateString(),
                'end' => $today->toDateString(),
            ],
            [
                'key' => 'last_30_days',
                'label' => 'Last 30 days',
                'start' => $today->subDays(29)->toDateString(),
                'end' => $today->toDateString(),
            ],
            [
                'key' => 'month_to_date',
                'label' => 'Month to date',
                'start' => $today->startOfMonth()->toDateString(),
                'end' => $today->toDateString(),
            ],
            [
                'key' => 'year_to_date',
                'label' => 'Year to date',
                'start' => $today->startOfYear()->toDateString(),
                'end' => $today->toDateString(),
            ],
        ];
    });
```

Each preset needs a unique `key`, a visible `label`, and valid `start` and `end` dates. The start date must not be after the end date.

### Keep the defaults and add more presets

Use the component in the preset closure when you want to extend the built-in list instead of replacing it:

```php
use RMRook\FilamentPeriodPicker\Forms\Components\PeriodPicker;

PeriodPicker::make('period')
    ->presets(fn (PeriodPicker $component): array => [
        ...$component->getDefaultPresets(),
        [
            'key' => 'launch',
            'label' => 'Product launch',
            'start' => '2026-09-01',
            'end' => '2026-10-15',
        ],
    ]);
```

### Only historical dates

This is useful for reports and analytics where future dates should not be selectable:

```php
PeriodPicker::make('reporting_period')
    ->minDate(fn () => now()->subYears(5)->startOfYear())
    ->maxDate(fn () => now());
```

### Only future dates

Use a moving window for bookings, planning, or availability:

```php
PeriodPicker::make('booking_period')
    ->minDate(fn () => today())
    ->maxDate(fn () => today()->addYear())
    ->presets(fn (): array => [
        [
            'key' => 'next_7_days',
            'label' => 'Next 7 days',
            'start' => today()->toDateString(),
            'end' => today()->addDays(6)->toDateString(),
        ],
        [
            'key' => 'next_30_days',
            'label' => 'Next 30 days',
            'start' => today()->toDateString(),
            'end' => today()->addDays(29)->toDateString(),
        ],
    ]);
```

### Localized European dates

Keep ISO dates in the form state while displaying a familiar localized format:

```php
use Carbon\CarbonInterface;

PeriodPicker::make('period')
    ->locale('nl')
    ->firstDayOfWeek(CarbonInterface::MONDAY)
    ->displayFormat('d-m-Y')
    ->format('Y-m-d');
```

To return another format from the field, change `format()`:

```php
PeriodPicker::make('period')
    ->displayFormat('d/m/Y')
    ->format('d/m/Y');
```

The resulting state is then:

```php
[
    'start' => '01/09/2026',
    'end' => '30/09/2026',
]
```

### Configure the start and end inputs differently

The parent field owns the final `start` and `end` state, while the two embedded `DatePicker` fields are available for presentation and input-specific behavior:

```php
use Filament\Forms\Components\DatePicker;

PeriodPicker::make('period')
    ->configureDatePickersUsing(
        fn (DatePicker $datePicker) => $datePicker
            ->extraInputAttributes(['autocomplete' => 'off'])
    )
    ->configureStartDatePickerUsing(
        fn (DatePicker $datePicker) => $datePicker
            ->label('Arrival')
            ->placeholder('Choose arrival date')
    )
    ->configureEndDatePickerUsing(
        fn (DatePicker $datePicker) => $datePicker
            ->label('Departure')
            ->placeholder('Choose departure date')
    );
```

## Working with the selected value

The field returns `null` until it has a valid start and end date. Once complete, read the two dates from the field state:

```php
$start = $data['period']['start'];
$end = $data['period']['end'];
```

When the range is stored directly in one Eloquent attribute, use a JSON column and cast it to an array:

```php
protected function casts(): array
{
    return [
        'period' => 'array',
    ];
}
```

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
