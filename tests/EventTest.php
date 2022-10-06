<?php

namespace Tests;

use CaesarGustav\Scheduler\Event;
use CaesarGustav\Scheduler\EventInterface;

it('implements the event interface', function() {
    $event = new Event(1000, today(), today());
    expect($event)->toBeInstanceOf(EventInterface::class);
});