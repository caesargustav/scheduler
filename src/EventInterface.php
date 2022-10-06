<?php

namespace CaesarGustav\Scheduler;

use Carbon\Carbon;

interface EventInterface
{
    public function getUuid(): string;

    public function getDuration(): int;

    public function getStart(): ?Carbon;

    public function getEnd(): ?Carbon;

    public function getOriginalEvent(): mixed;
}
