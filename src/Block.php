<?php

namespace CaesarGustav\Scheduler;

use Carbon\Carbon;

use Illuminate\Support\Collection;

class Block
{
    private Carbon $dateTime;
    private int $startDuration;
    private bool $isPlannable;
    private int $availableDuration;
    private Collection $plannedEvents;

    public function __construct(Carbon $dateTime, int $startDuration, ?bool $isPlannable)
    {
        $this->dateTime = $dateTime;
        $this->startDuration = $startDuration;
        $this->isPlannable = ! is_null($isPlannable) ? $isPlannable : true;
        $this->availableDuration = $startDuration;
        $this->plannedEvents = new Collection();
    }

    public function planEvent(EventInterface $event, int $duration): PlannedEvent
    {
        $availableDuration = $this->availableDuration;

        $plannableDuration = $duration;
        if ($availableDuration < $duration) {
            $plannableDuration = $availableDuration;
        }

        if ($event instanceof FixedEvent) {
            $plannableDuration = $event->getDuration();
        }

        $this->plannedEvents->push(new PlannedEvent($event, $this, $plannableDuration));
        $this->availableDuration -= $plannableDuration;

        return $this->plannedEvents->last();
    }

    public function getDateTime(): Carbon
    {
        return $this->dateTime;
    }

    public function getStartDuration(): int
    {
        return $this->startDuration;
    }

    public function isPlannable(): bool
    {
        return $this->isPlannable;
    }

    public function getAvailableDuration(): int
    {
        return $this->availableDuration;
    }

    public function getPlannedEvents(): Collection
    {
        return $this->plannedEvents;
    }
}
