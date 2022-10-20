<?php

namespace Tests;

use CaesarGustav\Scheduler\Block;
use CaesarGustav\Scheduler\PlannedEvent;
use Carbon\Carbon;

it('knows if an event was planned overdue', function () {
    $block = new Block(Carbon::parse('2022-10-21'), 1000);
    $plannedEvent = new PlannedEvent(getTestEventWithDuration(1000, '2022-10-21', '2022-10-22'), $block, 1000);

    expect($plannedEvent->isOverdue())->toBeFalse();

    $block = new Block(Carbon::parse('2022-10-21'), 1000);
    $plannedEvent = new PlannedEvent(getTestEventWithDuration(1000, '2022-09-21', '2022-09-22'), $block, 1000);

    expect($plannedEvent->isOverdue())->toBeTrue();

    $block = new Block(Carbon::parse('2022-10-21 08:00'), 1000);
    $plannedEvent = new PlannedEvent(getFixedTestEvent(Carbon::make('2022-10-21'), Carbon::make('2022-10-21')), $block, 10);

    expect($plannedEvent->isOverdue())->toBeFalse();
});
