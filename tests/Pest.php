<?php

use CaesarGustav\Scheduler\Event;
use CaesarGustav\Scheduler\FixedEvent;
use Carbon\Carbon;

function getTestEventWithDuration(int $duration = 1000, string $start = '2022-06-26', string $end = '2022-06-27'): Event
{
    return new Event($duration, Carbon::make($start), Carbon::make($end));
}

function getFixedTestEvent(
    ?Carbon $startTime = null,
    ?Carbon $endTime = null
): FixedEvent {
    if ($startTime === null) {
        $startTime = Carbon::now()->setHour(14)->setMinute(0)->setSecond(0);
    }
    if ($endTime === null) {
        $endTime = Carbon::now()->setHour(16)->setMinute(0)->setSecond(0);
    }

    return new FixedEvent($startTime, $endTime);
}
