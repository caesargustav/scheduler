<?php

namespace Tests\SkipRules;

use CaesarGustav\Scheduler\SkipRules\SkipDates;
use Carbon\Carbon;

it('validates given days against carbon dates', function () {
    $skipDateRule = new SkipDates('2022-03-28', '2022-03-30');

    expect($skipDateRule->isValid(Carbon::make('2022-03-28')))->toBeFalse()
        ->and($skipDateRule->isValid(Carbon::make('2022-03-30')))->toBeFalse()
        ->and($skipDateRule->isValid(Carbon::make('2022-03-31')))->toBeTrue()
        ->and($skipDateRule->isValid(Carbon::make('2022-04-01')))->toBeTrue();
});