<?php

namespace CaesarGustav\Scheduler\SkipRules;

use Carbon\Carbon;

class SkipDates extends AbstractSkipRule
{
    /** @var array<string, int|string> */
    private array $invalidDates;

    public function __construct(string ...$invalidDates)
    {
        $this->invalidDates = array_flip($invalidDates);
    }

    public function isValid(Carbon $date): bool
    {
        return ! isset($this->invalidDates[$date->toDateString()]);
    }
}
