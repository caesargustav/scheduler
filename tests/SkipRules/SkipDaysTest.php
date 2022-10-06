<?php

namespace Tests\SkipRules;

use CaesarGustav\Scheduler\SkipRules\SkipDays;
use Carbon\Carbon;

it('validates given days against carbon dates', function () {
    $skipDayRule = new SkipDays(SkipDays::MONDAY, SkipDays::THURSDAY);

    expect($skipDayRule->isValid(Carbon::make('2022-03-28')))->toBeFalse()
        ->and($skipDayRule->isValid(Carbon::make('2022-03-29')))->toBeTrue()
        ->and($skipDayRule->isValid(Carbon::make('2022-03-31')))->toBeFalse();
});