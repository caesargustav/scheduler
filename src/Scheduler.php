<?php

namespace CaesarGustav\Scheduler;

use CaesarGustav\Scheduler\SkipRules\AbstractSkipRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Scheduler
{
    private SchedulerBuilder $builder;
    /** @var Collection<int, FixedEvent> */
    private Collection $fixedEvents;
    /** @var Collection<int, Event> */
    private Collection $events;
    /** @var Collection<int, Block> */
    private Collection $blocks;
    private Carbon $dateTime;

    public static function builder(): SchedulerBuilder
    {
        return new SchedulerBuilder();
    }

    public function __construct(SchedulerBuilder $builder)
    {
        $this->builder = $builder;
        $this->fixedEvents = new Collection();
        $this->events = new Collection();
        $this->blocks = new Collection();
        $this->dateTime = $this->nextValidDate();
    }

    public function createBlock(
        Carbon $dateTime,
        ?int $duration = null,
        ?int $efficiencyOverride = null,
        ?bool $plannable = null
    ): Block {
        if ($this->blocks->contains(fn (Block $block) => $block->getDateTime()->isSameDay($dateTime))) {
            throw new InvalidArgumentException('For a given date only one block may be created.');
        }

        if (! is_null($duration) && $duration < 0) {
            throw new InvalidArgumentException('Duration must be positive.');
        }

        $block = new Block(
            $dateTime->copy()->startOfDay(),
            $this->builder->getBlockDuration($duration, $efficiencyOverride) ?? 0,
            $plannable
        );
        $this->blocks->push($block);

        return $block;
    }

    public function skip(): void
    {
        $this->dateTime = $this->nextValidDate();
    }

    public function addEvent(EventInterface $event): void
    {
        // We can not plan tasks without duration.
        if ($event->getDuration() === 0) {
            return;
        }

        if ($event instanceof FixedEvent) {
            $this->fixedEvents->push($event);
        }
        if ($event instanceof Event) {
            $this->events->push($event);
        }
    }

    /**
     * @return Collection<int, Block>
     */
    public function generate(): Collection
    {
        $this->fixedEvents
            ->each(fn (EventInterface $event) => $this->schedule($event));

        $this->events
            ->when(
                $this->builder->getSortEventsBy(),
                function (Collection $events) {
                    return $events->sortBy($this->builder->getSortEventsBy());
                }
            )
            ->each(fn (EventInterface $event) => $this->schedule($event));

        return $this->blocks;
    }

    private function schedule(EventInterface $event): void
    {
        if ($event instanceof FixedEvent) {
            if (! $this->isValid($event->getStart())) {
                return;
            }

            try {
                $block = $this->getBlockForDate($event->getStart());

                $block->planEvent($event, $event->getDuration());
            } catch (InvalidArgumentException $e) {
                // it might happen that the block is not plannable which results in an exception
                // the exception happens because the block is already present
            }
        }

        if ($event instanceof Event) {
            $remainingDuration = $event->getDuration();

            while ($remainingDuration > 0) {
                $block = $this->getBlockForDate($this->dateTime);
                if ($block->getAvailableDuration() <= 0 || ! $block->isPlannable()) {
                    $this->skip();

                    continue;
                }

                $plannedEvent = $block->planEvent($event, $remainingDuration);

                $remainingDuration -= $plannedEvent->getDuration();
            }
        }
    }

    private function getBlockForDate(Carbon $dateTime): Block
    {
        return $this->blocks->first(
            fn (Block $block) => $block->getDateTime()->isSameDay($dateTime)
        ) ?? $this->createBlock($dateTime);
    }

    private function nextValidDate(): Carbon
    {
        $dateTime = isset($this->dateTime) ? $this->dateTime->copy()->addDay() : today()->startOfDay();

        while (! $this->isValid($dateTime)) {
            $dateTime->addDay();
        }

        return $dateTime;
    }

    private function isValid(Carbon $date): bool
    {
        $valid = true;

        $this->builder->getSkipRules()->each(function (AbstractSkipRule $rule) use ($date, &$valid) {
            $valid = $rule->isValid($date);

            return $valid;
        });

        return $valid;
    }

    public function getBuilder(): SchedulerBuilder
    {
        return $this->builder;
    }

    public function getSchedule(): Schedule
    {
        return new Schedule($this->generate());
    }

    public function getDateTime(): Carbon
    {
        return $this->dateTime;
    }

    /**
     * @return Collection<int, FixedEvent>
     */
    public function getFixedEvents(): Collection
    {
        return $this->fixedEvents;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    /**
     * @return Collection<int, Block>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }
}
