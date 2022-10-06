<?php

namespace CaesarGustav\Scheduler\SkipRules;

class SkipWeekend extends SkipDays
{
    public function __construct()
    {
        parent::__construct(self::SATURDAY, self::SUNDAY);
    }
}
