<?php

namespace CaesarGustav\Scheduler;

use Carbon\Carbon;
use OutOfBoundsException;
use Ramsey\Uuid\Uuid;

class FixedEvent implements EventInterface
{
    private string $uuid;
    private Carbon $start;
    private Carbon $end;
    private ?int $duration;
    private mixed $originalEvent;

    public function __construct(
        Carbon $start,
        Carbon $end,
        ?int $duration = null,
        mixed $originalEvent = null
    ) {
        if (! $start->isSameDay($end)) {
            throw new OutOfBoundsException('Fixed events must have the same start and end date');
        }

        if ($end->isBefore($start)) {
            throw new OutOfBoundsException('End time must be after start time.');
        }

        $this->uuid = Uuid::uuid4();
        $this->duration = $duration;
        $this->start = $start;
        $this->end = $end;
        $this->originalEvent = $originalEvent;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getDuration(): int
    {
        return $this->duration ?? $this->end->diffInSeconds($this->start, absolute: true);
    }

    public function getOriginalEvent(): mixed
    {
        return $this->originalEvent;
    }

    public function getStart(): Carbon
    {
        return $this->start;
    }

    public function getEnd(): Carbon
    {
        return $this->end;
    }
}
