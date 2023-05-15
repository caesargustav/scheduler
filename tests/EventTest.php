<?php

use CaesarGustav\Scheduler\Event;
use CaesarGustav\Scheduler\EventInterface;

it('implements the event interface', function () {
    $event = new Event(1000, today(), today());
    expect($event)->toBeInstanceOf(EventInterface::class);
});

it('creates a unique hash for objects', function () {
    $originalEvent = new TestEvent();
    $event1 = new Event(1000, today(), today(), $originalEvent);
    $event2 = new Event(1000, today(), today(), $originalEvent);

    expect($event1->getHash())->toBe($event2->getHash());
});

class TestEvent
{
    public string $name;

    public function __construct()
    {
        $this->name = 'john doe';
    }
}
