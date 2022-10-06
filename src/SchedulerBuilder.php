<?php

namespace CaesarGustav\Scheduler;

use CaesarGustav\Scheduler\SkipRules\AbstractSkipRule;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SchedulerBuilder
{
    protected ?string $startOfBlock = '09:00';
    protected ?string $endOfBlock = '17:00';
    protected Collection $skipRules;
    protected ?int $blockDuration = null;
    protected int $efficiency = 80;

    public function __construct()
    {
        $this->skipRules = new Collection();
    }

    public function build(): Scheduler
    {
        $this->validate();

        return new Scheduler($this);
    }

    public function startOfBlock(?string $value): static
    {
        $this->startOfBlock = $value;

        return $this;
    }

    public function endOfBlock(?string $value): static
    {
        $this->endOfBlock = $value;

        return $this;
    }

    public function duration(?int $value): static
    {
        $this->blockDuration = $value;

        return $this;
    }

    public function efficiency(int $value): static
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('Efficiency must be between 0 and 100');
        }

        $this->efficiency = $value;

        return $this;
    }

    public function addSkipRule(AbstractSkipRule $rule): static
    {
        if ($this->skipRules->contains(function (AbstractSkipRule $skipRule, $key) use ($rule) {
            return class_basename($skipRule) === class_basename($rule);
        })) {
            throw new InvalidArgumentException('You may only add one rule of each type.');
        }

        $this->skipRules->push($rule);

        return $this;
    }

    public function getStartOfBlock(): ?string
    {
        return $this->startOfBlock;
    }

    public function getEndOfBlock(): ?string
    {
        return $this->endOfBlock;
    }

    /**
     * Return the duration of a block in seconds
     */
    public function getBlockDuration(?int $forDuration = null, ?int $efficiencyOverride = null): ?int
    {
        $blockDuration = $forDuration ?? $this->blockDuration;

        if (is_null($blockDuration)) {
            /** @phpstan-ignore-next-line we call the validate() function to ensure null is never passed here */
            $start = today()->setTimeFromTimeString($this->getStartOfBlock());
            /** @phpstan-ignore-next-line we call the validate() function to ensure null is never passed here */
            $end = today()->setTimeFromTimeString($this->getEndOfBlock());

            $blockDuration = $end->diffInSeconds($start);
        }

        return $blockDuration * ($efficiencyOverride ?? $this->getEfficiency()) / 100;
    }

    public function getEfficiency(): int
    {
        return $this->efficiency;
    }

    public function getSkipRules(): Collection
    {
        return $this->skipRules;
    }

    protected function validate(): void
    {
        // if either start or end block is null, duration must not be null
        if ($this->startOfBlock === null || $this->endOfBlock === null) {
            if ($this->blockDuration === null) {
                throw new InvalidArgumentException(
                    'Either start or end block must be set, or block duration must be set'
                );
            }
        }
    }
}