<?php

namespace CaesarGustav\Scheduler;

class PlannedEvent
{
    private EventInterface $event;
    private Block $block;
    private int $duration;

    public function __construct(EventInterface $event, Block $block, int $duration)
    {
        $this->event = $event;
        $this->block = $block;
        $this->duration = $duration;
    }

    public function getEvent(): EventInterface
    {
        return $this->event;
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function isOverdue(): bool
    {
        return $this->getBlock()->getDateTime()->startOfDay()->isAfter($this->getEvent()->getEnd()?->startOfDay());
    }

    public function isProblematic(): bool
    {
        return $this->isOverdue();
    }
}
