<?php

namespace Tests;

use CaesarGustav\Scheduler\EventInterface;
use CaesarGustav\Scheduler\FixedEvent;
use Carbon\Carbon;
use OutOfBoundsException;

it('implements the event interface', function () {
    $fixedEvent = new FixedEvent(Carbon::make('2018-01-01'), Carbon::make('2018-01-01'));
    expect($fixedEvent)->toBeInstanceOf(EventInterface::class);
});

it('can not span multiple days', function () {
    $this->expectException(OutOfBoundsException::class);
    new FixedEvent(Carbon::make('2018-01-01'), Carbon::make('2018-01-02'));
});

it('prevents to get confused if the day of the month is the same but the month not', function () {
    $this->expectException(OutOfBoundsException::class);
    new FixedEvent(Carbon::make('2018-01-01'), Carbon::make('2018-02-01'));
});

it('is not possible that the end time if before the start time', function () {
    $this->expectException(OutOfBoundsException::class);
    new FixedEvent(
        Carbon::make('2018-01-01 16:00'),
        Carbon::make('2018-01-01 14:00')
    );
});

it('correctly returns the event duration in seconds', function () {
    $fixedEvent = new FixedEvent(
        Carbon::make('2018-01-01 14:00'),
        Carbon::make('2018-01-01 16:00')
    );
    expect($fixedEvent->getDuration())->toBe(7200);
});

it('return the start and end time as a carbon instance', function () {
    $fixedEvent = new FixedEvent(Carbon::make('2018-01-01 14:00'), Carbon::make('2018-01-01 16:00'));
    expect($fixedEvent->getStart()->toDateTimeString())->toBe('2018-01-01 14:00:00')
        ->and($fixedEvent->getEnd()->toDateTimeString())->toBe('2018-01-01 16:00:00');
});

it('returns the manually set duration before the start and end time duration', function () {
    $fixedEvent = new FixedEvent(Carbon::make('2022-05-03 14:00'), Carbon::make('2022-05-03 16:00'), 100);
    expect($fixedEvent->getDuration())->toBe(100);
});
