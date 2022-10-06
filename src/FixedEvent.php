<?php

namespace CaesarGustav\Scheduler;

use Carbon\Carbon;
use OutOfBoundsException;
use Ramsey\Uuid\Uuid;

class FixedEvent implements EventInterface
{
    private string $uuid;
    private Carbon $startTime;
    private Carbon $endTime;
    private ?int $duration;
    private mixed $originalEvent;

    public function __construct(
        Carbon $startTime,
        Carbon $endTime,
        ?int $duration = null,
        mixed $originalEvent = null
    ) {
        if (! $startTime->isSameDay($endTime)) {
            throw new OutOfBoundsException('Fixed events must have the same start and end date');
        }

        if ($endTime->isBefore($startTime)) {
            throw new OutOfBoundsException('End time must be after start time.');
        }

        $this->uuid = Uuid::uuid4();
        $this->duration = $duration;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
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
        return $this->duration ?? $this->endTime->diffInSeconds($this->startTime);
    }

    public function getOriginalEvent(): mixed
    {
        return $this->originalEvent;
    }

    public function getStart(): Carbon
    {
        return $this->startTime;
    }

    public function getEnd(): Carbon
    {
        return $this->endTime;
    }
}
