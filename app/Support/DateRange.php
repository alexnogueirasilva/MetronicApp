<?php declare(strict_types = 1);

namespace App\Support;

use Illuminate\Support\Carbon;

readonly class DateRange
{
    private function __construct(private Carbon $start, private Carbon $end) {}

    public static function from(string $period, string $periodType): self
    {
        $date = Carbon::parse($period);

        return match ($periodType) {
            'monthly'   => new self($date->copy()->startOfMonth(), $date->copy()->endOfMonth()),
            'quarterly' => self::fromQuarter($date),
            'yearly'    => new self(
                /** @phpstan-ignore-next-line */
                Carbon::create($date->year, 1, 1)->startOfDay(),
                /** @phpstan-ignore-next-line */
                Carbon::create($date->year, 12, 31)->endOfDay()
            ),
            default => new self($date->copy()->startOfDay(), $date->copy()->endOfDay()),
        };
    }

    private static function fromQuarter(Carbon $date): self
    {
        $quarter    = (int) ceil($date->month / 3);
        $startMonth = ($quarter - 1) * 3 + 1;
        /** @phpstan-ignore-next-line */
        $start    = Carbon::create($date->year, $startMonth, 1)->startOfDay();
        $endMonth = $quarter * 3;
        /** @phpstan-ignore-next-line */
        $end = Carbon::create($date->year, $endMonth, 1)->endOfMonth();

        return new self($start, $end);
    }

    public function getStart(): Carbon
    {
        return $this->start;
    }

    public function getEnd(): Carbon
    {
        return $this->end;
    }
}
