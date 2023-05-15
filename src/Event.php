<?php

namespace CaesarGustav\Scheduler;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

class Event implements EventInterface
{
    private string $uuid;
    private int $duration;
    private ?Carbon $start;
    private ?Carbon $end;
    private mixed $originalEvent;
    private string $hash;

    public function __construct(int $duration, ?Carbon $start, ?Carbon $end, mixed $originalEvent = null)
    {
        $this->uuid = Uuid::uuid4();
        $this->duration = $duration;
        $this->start = $start;
        $this->end = $end;
        $this->originalEvent = $originalEvent;
        $this->hash = $this->generateHash();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getStart(): ?Carbon
    {
        return $this->start;
    }

    public function getEnd(): ?Carbon
    {
        return $this->end;
    }

    public function setStart(Carbon $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function setEnd(Carbon $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getOriginalEvent(): mixed
    {
        return $this->originalEvent;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    private function generateHash(): string
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();
        $hashString = '';

        foreach ($properties as $property) {
            if (in_array($property->getName(), ['uuid', 'hash']) === false) {
                $property->setAccessible(true);
                if ($property->getName() === 'originalEvent') {
                    $hashString .= json_encode($property->getValue($this));
                    continue;
                }
                $hashString .= $property->getValue($this);
            }
        }

        return md5($hashString);
    }
}
