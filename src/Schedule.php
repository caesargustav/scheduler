<?php

namespace CaesarGustav\Scheduler;

use Illuminate\Support\Collection;

class Schedule
{
    public function __construct(protected Collection $blocks)
    {
    }

    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function getAllEvents(): Collection
    {
        $events = new Collection();

        /** @var Block $block */
        foreach ($this->getBlocks() as $block) {
            $events = $events->merge($block->getPlannedEvents());
        }

        return $events;
    }

    public function getProblematicEvents(): Collection
    {
        return $this->getAllEvents()
            ->filter(fn (PlannedEvent $event) => $event->getEvent() instanceof Event)
            ->filter(fn (PlannedEvent $event) => $event->isProblematic())
            ->unique(fn (PlannedEvent $event) => $event->getEvent()->getUuid());
    }

    public function compare(Schedule $schedule): Collection
    {
        return $schedule->getProblematicEvents()->diffUsing($this->getProblematicEvents(), fn ($a, $b) => $a->getEvent()->getHash() <=> $b->getEvent()->getHash());
    }
}
