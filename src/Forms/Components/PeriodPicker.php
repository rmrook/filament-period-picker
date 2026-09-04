<?php

declare(strict_types=1);

namespace RMRook\FilamentPeriodPicker\Forms\Components;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Throwable;

class PeriodPicker extends Field
{
    /** @var view-string */
    protected string $view = 'filament-period-picker::forms.components.period-picker';

    protected ?Closure $configureDatePickersUsing = null;

    protected ?Closure $configureEndDatePickerUsing = null;

    protected ?Closure $configureStartDatePickerUsing = null;

    protected int|Closure $firstDayOfWeek = CarbonInterface::MONDAY;

    protected string|Closure|null $displayFormat = 'd M Y';

    protected string|Closure|null $format = 'Y-m-d';

    protected bool|Closure $isNative = false;

    protected string|Closure|null $locale = null;

    protected CarbonInterface|string|Closure|null $maxDate = null;

    protected CarbonInterface|string|Closure|null $minDate = null;

    protected bool|Closure $shouldCloseOnDateSelection = true;

    /**
     * @var array<int, array{key: string, label: string, start: string, end: string}>|Closure|null
     */
    protected array|Closure|null $presets = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(static function (PeriodPicker $component, mixed $state): void {
            $component->state($component->normalizeStateWithDraft($state));
        });

        $this->dehydrateStateUsing(fn (mixed $state): ?array => $this->formatStateForDehydration($state));

        $this->columns([
            'default' => 1,
            'sm' => 2,
        ]);

        $this->components(static function (PeriodPicker $component): array {
            $startDatePicker = DatePicker::make('draft_start')
                ->label(__('filament-period-picker::period-picker.start_date'))
                ->native($component->isNative())
                ->closeOnDateSelection($component->shouldCloseOnDateSelection())
                ->displayFormat($component->getDisplayFormat())
                ->format($component->getFormat())
                ->locale($component->getLocale())
                ->firstDayOfWeek($component->getFirstDayOfWeek())
                ->minDate($component->getMinDate())
                ->maxDate($component->getMaxDate());

            $endDatePicker = DatePicker::make('draft_end')
                ->label(__('filament-period-picker::period-picker.end_date'))
                ->native($component->isNative())
                ->closeOnDateSelection($component->shouldCloseOnDateSelection())
                ->displayFormat($component->getDisplayFormat())
                ->format($component->getFormat())
                ->locale($component->getLocale())
                ->firstDayOfWeek($component->getFirstDayOfWeek())
                ->minDate($component->getMinDate())
                ->maxDate($component->getMaxDate());

            return [
                $component->configureDatePicker($startDatePicker, 'start')->dehydrated(false),
                $component->configureDatePicker($endDatePicker, 'end')->dehydrated(false),
            ];
        });
    }

    public function closeOnDateSelection(bool|Closure $condition = true): static
    {
        $this->shouldCloseOnDateSelection = $condition;

        return $this;
    }

    public function configureDatePickersUsing(?Closure $callback): static
    {
        $this->configureDatePickersUsing = $callback;

        return $this;
    }

    public function configureEndDatePickerUsing(?Closure $callback): static
    {
        $this->configureEndDatePickerUsing = $callback;

        return $this;
    }

    public function configureStartDatePickerUsing(?Closure $callback): static
    {
        $this->configureStartDatePickerUsing = $callback;

        return $this;
    }

    public function default(mixed $state): static
    {
        parent::default(fn (): ?array => $this->normalizeStateWithDraft($this->evaluate($state)));

        return $this;
    }

    public function displayFormat(string|Closure|null $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    public function format(string|Closure|null $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function firstDayOfWeek(int|Closure $day): static
    {
        $this->firstDayOfWeek = $day;

        return $this;
    }

    public function getFirstDayOfWeek(): int
    {
        $day = (int) $this->evaluate($this->firstDayOfWeek);

        return in_array($day, range(0, 7), true) ? $day : 1;
    }

    public function getDisplayFormat(): string
    {
        return (string) ($this->evaluate($this->displayFormat) ?: 'd M Y');
    }

    public function getFormat(): string
    {
        return (string) ($this->evaluate($this->format) ?: 'Y-m-d');
    }

    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return (string) ($this->evaluate($this->locale) ?: app()->getLocale());
    }

    public function maxDate(CarbonInterface|string|Closure|null $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    public function getMaxDate(): ?string
    {
        return $this->normalizeDate($this->evaluate($this->maxDate));
    }

    public function minDate(CarbonInterface|string|Closure|null $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    public function getMinDate(): ?string
    {
        return $this->normalizeDate($this->evaluate($this->minDate));
    }

    public function isNative(): bool
    {
        return (bool) $this->evaluate($this->isNative);
    }

    public function native(bool|Closure $condition = true): static
    {
        $this->isNative = $condition;

        return $this;
    }

    /**
     * @param  array<int, array{key: string, label: string, start: string, end: string}>|Closure|null  $presets
     */
    public function presets(array|Closure|null $presets): static
    {
        $this->presets = $presets;

        return $this;
    }

    /**
     * @return array<int, array{key: string, label: string, start: string, end: string}>
     */
    public function getPresets(): array
    {
        $presets = $this->evaluate($this->presets) ?? $this->getDefaultPresets();

        return collect($presets)
            ->map(function (array $preset): ?array {
                $start = $this->normalizeDate($preset['start'] ?? null);
                $end = $this->normalizeDate($preset['end'] ?? null);

                if ((! $start) || (! $end) || ($start > $end)) {
                    return null;
                }

                return [
                    'key' => (string) ($preset['key'] ?? $start.'_'.$end),
                    'label' => (string) ($preset['label'] ?? ''),
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, start: string, end: string}>
     */
    public function getDefaultPresets(): array
    {
        $now = CarbonImmutable::now(config('app.user_timezone', config('app.timezone')));
        $previousQuarter = $now->subQuarter();

        return [
            $this->preset('this_year', __('filament-period-picker::period-picker.presets.this_year'), $now->startOfYear(), $now->endOfYear()),
            $this->preset('last_year', __('filament-period-picker::period-picker.presets.last_year'), $now->subYear()->startOfYear(), $now->subYear()->endOfYear()),
            $this->preset('this_quarter', __('filament-period-picker::period-picker.presets.this_quarter'), $now->startOfQuarter(), $now->endOfQuarter()),
            $this->preset('last_quarter', __('filament-period-picker::period-picker.presets.last_quarter'), $previousQuarter->startOfQuarter(), $previousQuarter->endOfQuarter()),
            $this->preset('this_month', __('filament-period-picker::period-picker.presets.this_month'), $now->startOfMonth(), $now->endOfMonth()),
            $this->preset('last_month', __('filament-period-picker::period-picker.presets.last_month'), $now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()),
            $this->preset('last_12_months', __('filament-period-picker::period-picker.presets.last_12_months'), $now->subYear()->addDay(), $now),
        ];
    }

    public function shouldCloseOnDateSelection(): bool
    {
        return (bool) $this->evaluate($this->shouldCloseOnDateSelection);
    }

    protected function configureDatePicker(DatePicker $datePicker, string $type): DatePicker
    {
        $injections = [
            'datePicker' => $datePicker,
            'type' => $type,
        ];
        $typedInjections = [
            DatePicker::class => $datePicker,
        ];

        $this->evaluate($this->configureDatePickersUsing, $injections, $typedInjections);
        $this->evaluate(
            $type === 'start' ? $this->configureStartDatePickerUsing : $this->configureEndDatePickerUsing,
            $injections,
            $typedInjections,
        );

        return $datePicker;
    }

    /**
     * @return array{key: string, label: string, start: string, end: string}
     */
    protected function preset(string $key, string $label, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    /** @return array{start: string, end: string}|null */
    protected function normalizeState(mixed $state): ?array
    {
        if (! is_array($state)) {
            return null;
        }

        $start = $this->normalizeDate($state['start'] ?? null);
        $end = $this->normalizeDate($state['end'] ?? null);

        if ((! $start) || (! $end) || ($start > $end)) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /** @return array{start: string, end: string}|null */
    protected function formatStateForDehydration(mixed $state): ?array
    {
        $state = $this->normalizeState($state);

        if (! $state) {
            return null;
        }

        $format = $this->getFormat();

        return [
            'start' => CarbonImmutable::createFromFormat('!Y-m-d', $state['start'])->format($format),
            'end' => CarbonImmutable::createFromFormat('!Y-m-d', $state['end'])->format($format),
        ];
    }

    /** @return array{start: string, end: string, draft_start: string, draft_end: string}|null */
    protected function normalizeStateWithDraft(mixed $state): ?array
    {
        $normalizedState = $this->normalizeState($state);

        if (! $normalizedState) {
            return null;
        }

        return [
            ...$normalizedState,
            'draft_start' => $this->normalizeDate($state['draft_start'] ?? null) ?? $normalizedState['start'],
            'draft_end' => $this->normalizeDate($state['draft_end'] ?? null) ?? $normalizedState['end'],
        ];
    }

    protected function normalizeDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->toDateString();
        }

        $date = (string) $date;
        $format = $this->getFormat();

        try {
            $formattedDate = CarbonImmutable::createFromFormat("!{$format}", $date);

            if ($formattedDate->format($format) === $date) {
                return $formattedDate->toDateString();
            }
        } catch (Throwable) {
            // Fall back to Carbon's flexible parser for ISO values and presets.
        }

        try {
            return CarbonImmutable::parse($date)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
