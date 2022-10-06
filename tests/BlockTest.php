<?php

namespace Tests;

use CaesarGustav\Scheduler\Block;
use Carbon\Carbon;

it('has the necessary getters to access the constructed object', function() {
    $now = Carbon::now();
    $block = new Block($now, 500, true);

    expect($block->getDateTime()->timestamp)->toBe($now->timestamp)
        ->and($block->getStartDuration())->toBe(500)
        ->and($block->getAvailableDuration())->toBe(500)
        ->and($block->getPlannedEvents())->toHaveCount(0)
        ->and($block->isPlannable())->toBeTrue();
});

it('plans an event', function () {
    $block = new Block(Carbon::now(), 500, true);
    $event = getTestEventWithDuration(400);

    $block->planEvent($event, $event->getDuration());

    expect($block->getPlannedEvents())->toHaveCount(1)
        ->and($block->getAvailableDuration())->toBe(100);
});

it('plans multiple event', function () {
    $block = new Block(Carbon::now(), 1000, true);
    $event = getTestEventWithDuration(300);
    $event2 = getTestEventWithDuration(400);

    $block->planEvent($event, $event->getDuration());
    $block->planEvent($event2, $event2->getDuration());

    expect($block->getPlannedEvents())->toHaveCount(2)
        ->and($block->getAvailableDuration())->toBe(300);
});
