<?php

namespace CaesarGustav\Scheduler\SkipRules;

use Carbon\Carbon;

class SkipDates extends AbstractSkipRule
{
    /** @var string[] */
    private array $invalidDates;

    public function __construct(string ...$invalidDates)
    {
        $this->invalidDates = $invalidDates;
    }

    public function isValid(Carbon $date): bool
    {
        return ! in_array($date->toDateString(), $this->invalidDates);
    }
}
