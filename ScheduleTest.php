<?php

namespace Tests;

use CaesarGustav\Scheduler\Event;
use CaesarGustav\Scheduler\Scheduler;
use Carbon\Carbon;

beforeEach(function() {
    Carbon::setTestNow('2022-07-15');
    $scheduler = Scheduler::builder()
        ->duration(1000)
        ->efficiency(100)
        ->build();

    $scheduler->addEvent(new Event(2000, null, Carbon::make('2022-07-14')));
    $scheduler->addEvent(new Event(4000, null, Carbon::make('2022-07-14')));

    $this->schedule = $scheduler->getSchedule();
});

it('can get all blocks', function() {
    expect($this->schedule->getBlocks())->toHaveCount(5);
});

it('can get all events', function() {
    expect($this->schedule->getAllEvents())->toHaveCount(5);
});

it('can get problematic events', function() {
    expect($this->schedule->getProblematicEvents())->toHaveCount(2);
});

it('filters problematic events for uniqueness', function() {
    expect($this->schedule->getProblematicEvents())->toHaveCount(2);
});
