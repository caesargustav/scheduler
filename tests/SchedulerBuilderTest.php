<?php

namespace Tests;

use CaesarGustav\Scheduler\Scheduler;
use CaesarGustav\Scheduler\SchedulerBuilder;
use CaesarGustav\Scheduler\SkipRules\SkipDates;
use CaesarGustav\Scheduler\SkipRules\SkipDays;

it('builds a scheduler', function () {
    $schedulerBuilder = new SchedulerBuilder();

    expect($schedulerBuilder->build())->toBeInstanceOf(Scheduler::class);
});

it('has defaults', function () {
    $schedulerBuilder = new SchedulerBuilder();

    expect($schedulerBuilder->getStartOfBlock())->toBe('09:00')
        ->and($schedulerBuilder->getEndOfBlock())->toBe('17:00')
        ->and($schedulerBuilder->getBlockDuration())->toBe(23040)
        ->and($schedulerBuilder->getEfficiency())->toBe(80);
});

it('can set options', function () {
    $schedulerBuilder = (new SchedulerBuilder())
        ->startOfBlock('10:00')
        ->endOfBlock('18:00')
        ->duration(100)
        ->efficiency(50);

    expect($schedulerBuilder->getStartOfBlock())->toBe('10:00')
        ->and($schedulerBuilder->getEndOfBlock())->toBe('18:00')
        ->and($schedulerBuilder->getBlockDuration())->toBe(50)
        ->and($schedulerBuilder->getEfficiency())->toBe(50);
});

it('validates that the efficiency can not below zero', function () {
    (new SchedulerBuilder())->efficiency(-1);
})->throws(\InvalidArgumentException::class);

it('validates that the efficiency can not above 100', function () {
    (new SchedulerBuilder())->efficiency(101);
})->throws(\InvalidArgumentException::class);

it('calculates the correct block duration', function () {
    $schedulerBuilder = (new SchedulerBuilder())
        ->startOfBlock('10:00')
        ->endOfBlock('18:00') // this sums up to 8 hours, or 480 minutes
        ->efficiency(50); // this means that the block duration is 480 minutes / 50% = 240 minutes

    expect($schedulerBuilder->getBlockDuration())->toBe(8 * 60 * 60 * 50 / 100);

    $schedulerBuilder
        ->startOfBlock(null)
        ->endOfBlock(null)
        ->duration(100);

    expect($schedulerBuilder->getBlockDuration())->toBe(50);

    $schedulerBuilder
        ->efficiency(100);

    expect($schedulerBuilder->getBlockDuration())->toBe(100);
});

it('validates that a duration of null is invalid if only the end date was given', function () {
    (new SchedulerBuilder())
        ->startOfBlock(null)
        ->endOfBlock('18:00')
        ->duration(null)
        ->build();
})->throws(\InvalidArgumentException::class);

it('validates that a duration of null is invalid if only the start date was given', function () {
    (new SchedulerBuilder())
        ->startOfBlock('10:00')
        ->endOfBlock(null)
        ->duration(null)
        ->build();
})->throws(\InvalidArgumentException::class);

it('validates that a duration of null is invalid if no dates were given', function () {
    (new SchedulerBuilder())
        ->startOfBlock(null)
        ->endOfBlock(null)
        ->duration(null)
        ->build();
})->throws(\InvalidArgumentException::class);

it('can add skip rules', function () {
    $scheduler = (new SchedulerBuilder())
        ->addSkipRule(new SkipDays(SkipDays::MONDAY))
        ->build();
    expect($scheduler->getBuilder()->getSkipRules())->toHaveCount(1);

    $scheduler = (new SchedulerBuilder())
        ->addSkipRule(new SkipDays(SkipDays::MONDAY))
        ->addSkipRule(new SkipDates())
        ->build();

    expect($scheduler->getBuilder()->getSkipRules())->toHaveCount(2);
});

it('can accept and return a closure for sorting', function () {
    $builder = (new SchedulerBuilder())
        ->sortEventsBy([function ($a, $b) {
            return $a->getStart()->getTimestamp() <=> $b->getStart()->getTimestamp();
        }]);

    expect($builder->getSortEventsBy())->toBeArray();
});
