<?php

namespace CaesarGustav\Scheduler\SkipRules;

use Carbon\Carbon;

abstract class AbstractSkipRule
{
    abstract public function isValid(Carbon $date): bool;
}
