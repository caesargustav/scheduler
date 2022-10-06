<?php

namespace Tests\SkipRules;

use CaesarGustav\Scheduler\SkipRules\SkipWeekend;
use Carbon\Carbon;

it('validates if a given day is on the weekend', function () {
    $skipDateRule = new SkipWeekend();

    expect($skipDateRule->isValid(Carbon::make('2022-03-25')))->toBeTrue()
        ->and($skipDateRule->isValid(Carbon::make('2022-03-26')))->toBeFalse()
        ->and($skipDateRule->isValid(Carbon::make('2022-03-27')))->toBeFalse()
        ->and($skipDateRule->isValid(Carbon::make('2022-03-28')))->toBeTrue()
        ->and($skipDateRule->isValid(Carbon::make('2022-03-29')))->toBeTrue();
});
