<?php

namespace CaesarGustav\Scheduler\SkipRules;

use Carbon\Carbon;

class SkipDays extends AbstractSkipRule
{
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const SUNDAY = 7;

    private array $invalidDays;

    public function __construct(int ...$days)
    {
        $this->invalidDays = $days;
    }

    public function isValid(Carbon $date): bool
    {
        return ! in_array($date->isoWeekday(), $this->invalidDays);
    }
}
