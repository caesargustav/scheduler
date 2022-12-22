<?php

namespace Tests;

use CaesarGustav\Scheduler\Block;
use CaesarGustav\Scheduler\Event;
use CaesarGustav\Scheduler\FixedEvent;
use CaesarGustav\Scheduler\Schedule;
use CaesarGustav\Scheduler\Scheduler;
use CaesarGustav\Scheduler\SchedulerBuilder;
use CaesarGustav\Scheduler\SkipRules\SkipDates;
use CaesarGustav\Scheduler\SkipRules\SkipDays;
use CaesarGustav\Scheduler\SkipRules\SkipWeekend;
use Carbon\Carbon;

beforeEach(fn () => $this->scheduler = buildScheduler());

function buildScheduler(): Scheduler
{
    return Scheduler::builder()
        ->duration(1000)
        ->efficiency(100)
        ->build();
}

it('can add events to the scheduler', function () {
    $this->scheduler->addEvent(getTestEventWithDuration());

    expect($this->scheduler->getEvents())->toHaveCount(1);
});

it('created blocks on demand if the events duration demands it', function () {
    $this->scheduler->addEvent(getTestEventWithDuration(1500));
    $this->scheduler->generate();

    expect($this->scheduler->getEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks())->toHaveCount(2)
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents()->first()->getDuration())->toBe(1000)
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents()->last()->getDuration())->toBe(500);
});

it('events with a fixed date are correctly planned', function () {
    $this->scheduler->addEvent(
        getFixedTestEvent(Carbon::make('2022-02-17 14:00:00'), Carbon::make('2022-02-17 14:15:00'))
    ); // 900 seconds
    $this->scheduler->generate();

    expect($this->scheduler->getFixedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->first()->getDateTime()->format('Y-m-d'))->toBe('2022-02-17')
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(1);
});

it('plans unfixed events correct if fixed events are present', function () {
    Carbon::setTestNow('2022-01-17');
    $this->scheduler = buildScheduler();

    $this->scheduler->addEvent(
        getFixedTestEvent(Carbon::make('2022-02-17 14:00:00'), Carbon::make('2022-02-17 14:15:00'))
    );
    $this->scheduler->addEvent(getTestEventWithDuration());
    $this->scheduler->generate();

    expect($this->scheduler->getFixedEvents())->toHaveCount(1)
        ->and($this->scheduler->getEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks())->toHaveCount(2)
        ->and($this->scheduler->getBlocks()->first()->getDateTime()->toDateString())->toBe('2022-02-17')
        ->and($this->scheduler->getBlocks()->last()->getDateTime()->toDateString())->toBe('2022-01-17')
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents())->toHaveCount(1);
});

it('can plan unfixed and fixed events on the same day', function () {
    Carbon::setTestNow('2022-01-17');
    $this->scheduler = buildScheduler();

    $this->scheduler->addEvent(
        getFixedTestEvent(Carbon::make('2022-01-17 14:00:00'), Carbon::make('2022-01-17 14:08:00'))
    );
    $this->scheduler->addEvent(getTestEventWithDuration(500));
    $this->scheduler->generate();

    $this->assertCount(1, $this->scheduler->getFixedEvents());
    $this->assertCount(1, $this->scheduler->getEvents());
    $this->assertCount(1, $this->scheduler->getBlocks());
    $this->assertSame('2022-01-17', $this->scheduler->getBlocks()->first()->getDateTime()->toDateString());
    $this->assertCount(2, $this->scheduler->getBlocks()->first()->getPlannedEvents());

    expect($this->scheduler->getFixedEvents())->toHaveCount(1)
        ->and($this->scheduler->getEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->first()->getDateTime()->toDateString())->toBe('2022-01-17')
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(2);
});

it('plans fixed events on the correct date regardless of other present blocks', function () {
    Carbon::setTestNow('2022-01-17');
    $this->scheduler = buildScheduler();

    $this->scheduler->addEvent(
        getFixedTestEvent(Carbon::make('2022-02-17 14:00:00'), Carbon::make('2022-02-17 14:08:00'))
    );
    $this->scheduler->addEvent(getTestEventWithDuration(500));
    $this->scheduler->generate();

    expect($this->scheduler->getFixedEvents())->toHaveCount(1)
        ->and($this->scheduler->getEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks())->toHaveCount(2)
        ->and($this->scheduler->getBlocks()->first()->getDateTime()->toDateString())->toBe('2022-02-17')
        ->and($this->scheduler->getBlocks()->last()->getDateTime()->toDateString())->toBe('2022-01-17')
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents()->first()->getEvent())->toBeInstanceOf(
            FixedEvent::class
        )
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents())->toHaveCount(1);
});

it('creates new blocks for events if fixed events use up all of the blocks duration', function () {
    Carbon::setTestNow('2022-01-17');
    $this->scheduler = buildScheduler();

    $this->scheduler->addEvent(getFixedTestEvent());
    $this->scheduler->addEvent(getTestEventWithDuration(500));
    $this->scheduler->generate();

    expect($this->scheduler->getFixedEvents())->toHaveCount(1)
        ->and($this->scheduler->getEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks())->toHaveCount(2)
        ->and($this->scheduler->getBlocks()->first()->getDateTime()->toDateString())->toBe('2022-01-17')
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents()->first()->getEvent())->toBeInstanceOf(
            FixedEvent::class
        )
        ->and($this->scheduler->getBlocks()->last()->getDateTime()->toDateString())->toBe('2022-01-18')
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents())->toHaveCount(1);
});

it('can override the scheduler builders block duration on a per block basis', function () {
    $this->scheduler->addEvent(getTestEventWithDuration(1500));
    $this->scheduler->createBlock(Carbon::now(), 1000);

    expect($this->scheduler->getBlocks())->toHaveCount(1);

    $this->scheduler->generate();
    expect($this->scheduler->getEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks())->toHaveCount(2)
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents())->toHaveCount(1)
        ->and($this->scheduler->getBlocks()->first()->getPlannedEvents()->first()->getDuration())->toBe(1000)
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents()->first()->getDuration())->toBe(500);
});

it('creates blocks that have a positive duration', function () {
    $this->scheduler->createBlock(Carbon::now(), 199);

    expect($this->scheduler->getBlocks())->toHaveCount(1);
});

it('throws an exception of you try to create a block with a negative duration', function () {
    $this->scheduler->createBlock(Carbon::now(), -199);
})->throws(\InvalidArgumentException::class);

it('creates the first block with todays date', function () {
    $scheduler = Scheduler::builder()
        ->build();
    $scheduler->addEvent(getTestEventWithDuration(1500));
    $scheduler->generate();

    expect($scheduler->getBlocks()->first()->getDateTime()->toDateString())->toBe(today()->toDateString());
});

it('respects the provided validation rules', function () {
    Carbon::setTestNow('2022-01-17'); // Monday

    $scheduler = Scheduler::builder()
        ->duration(1000)
        ->efficiency(100)
        ->addSkipRule(new SkipDays(SkipDays::MONDAY))
        ->addSkipRule(new SkipDates('2022-01-18'))
        ->addSkipRule(new SkipWeekend())
        ->build();

    // This should add 5 blocks in total
    $scheduler->addEvent(getTestEventWithDuration(5000));
    $blocks = $scheduler->generate();

    // With the above rules the following dates should be generated:
    // 2022-01-19 (Wednesday)
    // 2022-01-20 (Thursday)
    // 2022-01-21 (Friday)
    // 2022-01-25 (Tuesday)
    // 2022-01-26 (Wednesday)

    $expectedDates = [
        '2022-01-19',
        '2022-01-20',
        '2022-01-21',
        '2022-01-25',
        '2022-01-26',
    ];

    $blockDates = $blocks->map(function (Block $block) {
        return $block->getDateTime()->toDateString();
    });

    expect($blocks)->toHaveCount(5)
        ->and($blockDates->toArray())->toEqualCanonicalizing($expectedDates);
});

it('makes sure fixed events are not planned on skipped blocks', function () {
    Carbon::setTestNow('2022-01-17'); // Monday

    $scheduler = Scheduler::builder()
        ->duration(1000)
        ->efficiency(100)
        ->addSkipRule(new SkipDays(SkipDays::MONDAY))
        ->build();

    // This should add 0 blocks in total
    $scheduler->addEvent(getFixedTestEvent(Carbon::make('2022-01-17 14:00'), Carbon::make('2022-01-17 16:00')));
    $blocks = $scheduler->generate();

    expect($blocks)->toHaveCount(0);
});

it('respects the set efficiency when manually creating blocks', function () {
    $scheduler = Scheduler::builder()
        ->duration(1000)
        ->efficiency(50)
        ->build();

    $scheduler->createBlock(Carbon::now(), 500);
    expect($scheduler->getBlocks()->first()->getStartDuration())->toBe(250);
});

it('can create blocks with a specific date', function () {
    $scheduler = $this->scheduler;

    $scheduler->createBlock(Carbon::make('2022-11-17'));
    expect($scheduler->getBlocks()->first()->getDateTime()->toDateString())->toBe('2022-11-17');
});

it('makes the builder instance publicly available', function () {
    expect($this->scheduler->getBuilder())->toBeInstanceOf(SchedulerBuilder::class);
});

it('can skip dates publicly', function () {
    Carbon::setTestNow('2020-02-02');
    $scheduler = buildScheduler();

    expect($scheduler->getDateTime()->toDateString())->toBe('2020-02-02');

    $scheduler->skip();
    expect($scheduler->getDateTime()->toDateString())->toBe('2020-02-03');
});

it('returns a schedule object which contains the generated schedule', function () {
    $this->scheduler->addEvent(getTestEventWithDuration(1500));
    $this->scheduler->generate();

    expect($this->scheduler->getSchedule())->toBeInstanceOf(Schedule::class);
});

it('generates the schedule each time the getter for the schedule object is called', function () {
    $this->scheduler->addEvent(getTestEventWithDuration(1500));
    $schedule = $this->scheduler->getSchedule();
    expect($schedule->getBlocks())->toHaveCount(2);

    $this->scheduler->addEvent(getTestEventWithDuration(1500));
    $schedule = $this->scheduler->getSchedule();
    expect($schedule->getBlocks())->toHaveCount(5);
});

it('throws an expection when trying to create a block that already exists', function () {
    $this->scheduler->createBlock(Carbon::parse('2022-09-01'));

    $this->scheduler->createBlock(Carbon::parse('2022-09-01'));
})->throws(\InvalidArgumentException::class);

it('can create unplannable blocks', function () {
    $this->scheduler->createBlock(Carbon::make('2022-11-17'));
    expect($this->scheduler->getBlocks()->first()->isPlannable())->toBeTrue();

    $this->scheduler->createBlock(Carbon::make('2022-11-18'), null, null, false);
    expect($this->scheduler->getBlocks()->last()->isPlannable())->toBeFalse();
});

it('does not plan events on an unplannable block', function () {
    Carbon::setTestNow('2022-01-17');
    $this->scheduler->createBlock(Carbon::parse('2022-01-17'), null, null, false);

    $this->scheduler->addEvent(getTestEventWithDuration());
    $this->scheduler->generate();

    expect($this->scheduler->getBlocks()->first()->getPlannedEvents())->toHaveCount(0)
        ->and($this->scheduler->getBlocks()->first()->isPlannable())->toBeFalse()
        ->and($this->scheduler->getBlocks()->last()->getPlannedEvents())->toHaveCount(1);
});

it('can provide a custom function to sort events', function () {
    $schedulerWithoutSort = Scheduler::builder()
        ->duration(10000)
        ->efficiency(100)
        ->build();

    $schedulerWithoutSort->addEvent(getTestEventWithDuration(300));
    $schedulerWithoutSort->addEvent(getTestEventWithDuration(500));
    $schedulerWithoutSort->addEvent(getTestEventWithDuration(400));

    $scheduleWithoutSort = $schedulerWithoutSort->getSchedule();
    expect($scheduleWithoutSort->getBlocks()->first()->getPlannedEvents()[0]->getDuration())->toBe(300);
    expect($scheduleWithoutSort->getBlocks()->first()->getPlannedEvents()[1]->getDuration())->toBe(500);
    expect($scheduleWithoutSort->getBlocks()->first()->getPlannedEvents()[2]->getDuration())->toBe(400);

    $schedulerWithSort = Scheduler::builder()
        ->duration(10000)
        ->efficiency(100)
        ->sortEventsBy(
            [
                fn (Event $a, Event $b) => $b->getDuration() <=> $a->getDuration(),
            ]
        )
        ->build();

    $schedulerWithSort->addEvent(getTestEventWithDuration(300));
    $schedulerWithSort->addEvent(getTestEventWithDuration(500));
    $schedulerWithSort->addEvent(getTestEventWithDuration(400));

    $scheduleWithSort = $schedulerWithSort->getSchedule();
    expect($scheduleWithSort->getBlocks()->first()->getPlannedEvents()[0]->getDuration())->toBe(500);
    expect($scheduleWithSort->getBlocks()->first()->getPlannedEvents()[1]->getDuration())->toBe(400);
    expect($scheduleWithSort->getBlocks()->first()->getPlannedEvents()[2]->getDuration())->toBe(300);
});
