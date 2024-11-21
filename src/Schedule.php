<?php

namespace CaesarGustav\Scheduler;

use Illuminate\Support\Collection;

class Schedule
{
    /**
     * @param Collection<int, Block> $blocks
     */
    public function __construct(protected Collection $blocks)
    {
    }

    /**
     * @return Collection<int, Block>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    /**
     * @return Collection<int, PlannedEvent>
     */
    public function getAllEvents(): Collection
    {
        $events = new Collection();

        /** @var Block $block */
        foreach ($this->getBlocks() as $block) {
            $events = $events->merge($block->getPlannedEvents());
        }

        return $events;
    }

    /**
     * @return Collection<int, PlannedEvent>
     */
    public function getProblematicEvents(): Collection
    {
        return $this->getAllEvents()
            ->filter(fn (PlannedEvent $event) => $event->getEvent() instanceof Event)
            ->filter(fn (PlannedEvent $event) => $event->isProblematic())
            ->unique(fn (PlannedEvent $event) => $event->getEvent()->getUuid());
    }

    /**
     * @return Collection<int, PlannedEvent>
     */
    public function compare(Schedule $schedule): Collection
    {
        return $schedule->getProblematicEvents()->diffUsing($this->getProblematicEvents(), fn ($a, $b) => $a->getEvent()->getHash() <=> $b->getEvent()->getHash());
    }
}
