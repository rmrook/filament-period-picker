<?php

declare(strict_types=1);

namespace RMRook\FilamentPeriodPicker\Tests;

use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Livewire\Component;
use ReflectionMethod;
use RMRook\FilamentPeriodPicker\Forms\Components\PeriodPicker;
use RMRook\FilamentPeriodPicker\PeriodPickerPlugin;

final class PeriodPickerTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_is_a_filament_plugin(): void
    {
        $this->assertSame('rmr-period-picker', PeriodPickerPlugin::make()->getId());
    }

    public function test_it_provides_default_period_presets(): void
    {
        CarbonImmutable::setTestNow('2026-09-03 12:00:00');

        $presets = collect(PeriodPicker::make('period')->getDefaultPresets())->keyBy('key');

        $this->assertSame('2026-01-01', $presets['this_year']['start']);
        $this->assertSame('2026-12-31', $presets['this_year']['end']);
        $this->assertSame('2025-01-01', $presets['last_year']['start']);
        $this->assertSame('2025-12-31', $presets['last_year']['end']);
        $this->assertSame('2026-07-01', $presets['this_quarter']['start']);
        $this->assertSame('2026-09-30', $presets['this_quarter']['end']);
        $this->assertSame('2026-06-30', $presets['last_quarter']['end']);
        $this->assertSame('2026-08-01', $presets['last_month']['start']);
        $this->assertSame('2026-08-31', $presets['last_month']['end']);
        $this->assertSame('2025-09-04', $presets['last_12_months']['start']);
        $this->assertSame('2026-09-03', $presets['last_12_months']['end']);
    }

    public function test_it_accepts_custom_presets_and_rejects_invalid_ranges(): void
    {
        $presets = PeriodPicker::make('period')
            ->presets([
                [
                    'key' => 'valid',
                    'label' => 'Valid',
                    'start' => '2026-01-01',
                    'end' => '2026-01-31',
                ],
                [
                    'key' => 'invalid',
                    'label' => 'Invalid',
                    'start' => '2026-02-01',
                    'end' => '2026-01-01',
                ],
            ])
            ->getPresets();

        $this->assertCount(1, $presets);
        $this->assertSame('valid', $presets[0]['key']);
    }

    public function test_it_evaluates_date_picker_configuration(): void
    {
        $component = PeriodPicker::make('period')
            ->closeOnDateSelection(fn (): bool => false)
            ->displayFormat(fn (): string => 'd/m/Y')
            ->firstDayOfWeek(fn (): int => 7)
            ->format(fn (): string => 'd/m/Y')
            ->native(fn (): bool => true);

        $this->assertFalse($component->shouldCloseOnDateSelection());
        $this->assertSame('d/m/Y', $component->getDisplayFormat());
        $this->assertSame(7, $component->getFirstDayOfWeek());
        $this->assertSame('d/m/Y', $component->getFormat());
        $this->assertTrue($component->isNative());
    }

    public function test_it_normalizes_and_dehydrates_dates_using_the_configured_format(): void
    {
        $component = PeriodPicker::make('period')->format('d/m/Y');
        $normalizeState = new ReflectionMethod($component, 'normalizeState');
        $formatStateForDehydration = new ReflectionMethod($component, 'formatStateForDehydration');

        $this->assertSame(
            ['start' => '2026-09-03', 'end' => '2026-09-30'],
            $normalizeState->invoke($component, ['start' => '03/09/2026', 'end' => '30/09/2026']),
        );
        $this->assertSame(
            ['start' => '03/09/2026', 'end' => '30/09/2026'],
            $formatStateForDehydration->invoke($component, ['start' => '2026-09-03', 'end' => '2026-09-30']),
        );
    }

    public function test_it_hydrates_a_default_range_into_the_embedded_date_pickers(): void
    {
        $hydratedDrafts = [];
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;

            /** @var array<string, mixed> */
            public array $data = [];
        };

        $schema = Schema::make($livewire)
            ->statePath('data')
            ->components([
                PeriodPicker::make('period')
                    ->default(fn (): array => [
                        'start' => '2026-09-01',
                        'end' => '2026-09-30',
                    ])
                    ->configureDatePickersUsing(function (DatePicker $datePicker, string $type) use (&$hydratedDrafts): void {
                        $datePicker->afterStateHydrated(function (mixed $state) use (&$hydratedDrafts, $type): void {
                            $hydratedDrafts[$type] = $state;
                        });
                    }),
            ]);

        $schema->fill();

        $this->assertSame([
            'start' => '2026-09-01',
            'end' => '2026-09-30',
            'draft_start' => '2026-09-01',
            'draft_end' => '2026-09-30',
        ], $livewire->data['period']);
        $this->assertSame([
            'start' => '2026-09-01',
            'end' => '2026-09-30',
        ], $hydratedDrafts);
    }

    public function test_it_allows_both_date_pickers_to_be_further_configured(): void
    {
        $component = PeriodPicker::make('period')
            ->configureDatePickersUsing(function (DatePicker $datePicker, string $type): void {
                $datePicker->placeholder("Choose the {$type} date");
            })
            ->configureEndDatePickerUsing(function (DatePicker $datePicker): void {
                $datePicker->label('Ending date');
            });
        $configureDatePicker = new ReflectionMethod($component, 'configureDatePicker');

        /** @var DatePicker $startDatePicker */
        $startDatePicker = $configureDatePicker->invoke($component, DatePicker::make('draft_start'), 'start');
        /** @var DatePicker $endDatePicker */
        $endDatePicker = $configureDatePicker->invoke($component, DatePicker::make('draft_end'), 'end');

        $this->assertSame('Choose the start date', $startDatePicker->getPlaceholder());
        $this->assertSame('Choose the end date', $endDatePicker->getPlaceholder());
        $this->assertSame('Ending date', $endDatePicker->getLabel());
    }

    public function test_all_translations_contain_the_english_translation_keys(): void
    {
        $languageDirectory = __DIR__.'/../resources/lang';
        $englishKeys = array_keys(Arr::dot(require $languageDirectory.'/en/period-picker.php'));

        foreach (['de', 'el', 'es', 'fr', 'it', 'ja', 'nl'] as $locale) {
            $translationKeys = array_keys(Arr::dot(require "{$languageDirectory}/{$locale}/period-picker.php"));

            $this->assertSame($englishKeys, $translationKeys, "The [{$locale}] translations do not match the English translation keys.");
        }
    }

    public function test_it_ships_a_reusable_filament_field_view(): void
    {
        $component = PeriodPicker::make('period');
        $view = file_get_contents(__DIR__.'/../resources/views/forms/components/period-picker.blade.php');

        $this->assertSame('filament-period-picker::forms.components.period-picker', $component->getView());
        $this->assertTrue(view()->exists($component->getView()));
        $this->assertIsString($view);
        $this->assertStringContainsString('periodPickerFormComponent', $view);
        $this->assertStringContainsString('rmr-period-picker__panel', $view);
        $this->assertStringContainsString('rmr-period-picker__date-inputs', $view);
        $this->assertStringContainsString('rmr-period-picker__calendars', $view);
        $this->assertStringContainsString('rmr-period-picker__footer', $view);
        $this->assertStringContainsString('aria-modal="true"', $view);
        $this->assertStringContainsString('x-trap="open"', $view);
        $this->assertStringContainsString('calendarDays(visibleMonth)', $view);
        $this->assertStringContainsString('chooseDate(day.value)', $view);
        $this->assertStringNotContainsString('type="date"', $view);

        $script = file_get_contents(__DIR__.'/../resources/js/period-picker.js');

        $this->assertIsString($script);
        $this->assertStringContainsString("replaceAll('_', '-')", $script);
    }

    public function test_it_ships_a_mobile_viewport_safe_layout(): void
    {
        $styles = file_get_contents(__DIR__.'/../resources/css/period-picker.css');

        $this->assertIsString($styles);
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        $this->assertStringContainsString('position: fixed !important', $styles);
        $this->assertStringContainsString('height: 100dvh', $styles);
        $this->assertStringContainsString('overflow-x: hidden', $styles);
        $this->assertStringContainsString('overflow-y: auto', $styles);
        $this->assertStringContainsString('safe-area-inset-bottom', $styles);
        $this->assertStringContainsString('@media (max-width: 359px)', $styles);
    }
}
