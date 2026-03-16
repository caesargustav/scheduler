<?php

use CaesarGustav\Scheduler\Event;
use CaesarGustav\Scheduler\FixedEvent;
use CaesarGustav\Scheduler\Schedule;
use CaesarGustav\Scheduler\Scheduler;
use CaesarGustav\Scheduler\SkipRules\SkipWeekend;
use Carbon\Carbon;

function buildBenchmarkScheduler(): Scheduler
{
    Carbon::setTestNow('2026-03-16');

    $scheduler = Scheduler::builder()
        ->startOfBlock('09:00')
        ->endOfBlock('17:00')
        ->efficiency(80)
        ->addSkipRule(new SkipWeekend())
        ->build();

    // 50 fixed events spread across the next ~70 calendar days (~50 workdays)
    $baseDate = Carbon::make('2026-03-16');
    $workday = 0;
    $fixedAdded = 0;
    $day = $baseDate->copy();

    while ($fixedAdded < 50) {
        $day = $baseDate->copy()->addDays($workday);
        if ($day->isWeekend()) {
            $workday++;
            continue;
        }

        $start = $day->copy()->setHour(14)->setMinute(0)->setSecond(0);
        $end = $day->copy()->setHour(14)->setMinute(30)->setSecond(0);
        $scheduler->addEvent(new FixedEvent($start, $end));
        $fixedAdded++;
        $workday++;
    }

    // 50 normal events with varying durations (1h to 8h in seconds)
    $durations = [
        3600, 7200, 1800, 14400, 5400,
        10800, 28800, 3600, 9000, 7200,
        1800, 21600, 5400, 14400, 3600,
        10800, 7200, 28800, 1800, 9000,
        5400, 3600, 14400, 7200, 10800,
        1800, 28800, 9000, 3600, 5400,
        21600, 7200, 14400, 1800, 10800,
        3600, 9000, 28800, 5400, 7200,
        1800, 14400, 10800, 3600, 21600,
        9000, 7200, 5400, 28800, 1800,
    ];

    foreach ($durations as $i => $duration) {
        $start = Carbon::make('2026-03-16');
        $end = Carbon::make('2026-06-30');
        $scheduler->addEvent(new Event($duration, $start, $end));
    }

    return $scheduler;
}

function runBenchmark(int $iterations = 20): array
{
    $times = [];

    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $scheduler = buildBenchmarkScheduler();
        $schedule = $scheduler->getSchedule();
        $end = hrtime(true);
        $times[] = ($end - $start) / 1_000_000; // ms
    }

    return $times;
}

function captureScheduleSnapshot(Schedule $schedule): array
{
    $snapshot = [];

    foreach ($schedule->getBlocks() as $block) {
        $blockData = [
            'date' => $block->getDateTime()->toDateString(),
            'startDuration' => $block->getStartDuration(),
            'availableDuration' => $block->getAvailableDuration(),
            'isPlannable' => $block->isPlannable(),
            'events' => [],
        ];

        foreach ($block->getPlannedEvents() as $pe) {
            $blockData['events'][] = [
                'hash' => $pe->getEvent()->getHash(),
                'duration' => $pe->getDuration(),
                'isFixed' => $pe->getEvent() instanceof FixedEvent,
            ];
        }

        $snapshot[] = $blockData;
    }

    return $snapshot;
}

// ── Correctness tests ──

test('benchmark scheduler produces consistent results', function () {
    Carbon::setTestNow('2026-03-16');

    $scheduler1 = buildBenchmarkScheduler();
    $schedule1 = $scheduler1->getSchedule();

    $scheduler2 = buildBenchmarkScheduler();
    $schedule2 = $scheduler2->getSchedule();

    $snap1 = captureScheduleSnapshot($schedule1);
    $snap2 = captureScheduleSnapshot($schedule2);

    expect($snap1)->toBe($snap2);
});

test('benchmark scheduler plans all events', function () {
    Carbon::setTestNow('2026-03-16');

    $scheduler = buildBenchmarkScheduler();
    $schedule = $scheduler->getSchedule();

    expect($scheduler->getFixedEvents())->toHaveCount(50);
    expect($scheduler->getEvents())->toHaveCount(50);

    // all blocks should exist
    expect($schedule->getBlocks()->count())->toBeGreaterThan(0);

    // all planned event durations for normal events should sum to total input duration
    $durations = [
        3600, 7200, 1800, 14400, 5400,
        10800, 28800, 3600, 9000, 7200,
        1800, 21600, 5400, 14400, 3600,
        10800, 7200, 28800, 1800, 9000,
        5400, 3600, 14400, 7200, 10800,
        1800, 28800, 9000, 3600, 5400,
        21600, 7200, 14400, 1800, 10800,
        3600, 9000, 28800, 5400, 7200,
        1800, 14400, 10800, 3600, 21600,
        9000, 7200, 5400, 28800, 1800,
    ];
    $expectedTotal = array_sum($durations);

    $normalPlannedTotal = $schedule->getAllEvents()
        ->filter(fn ($pe) => $pe->getEvent() instanceof Event)
        ->sum(fn ($pe) => $pe->getDuration());

    expect($normalPlannedTotal)->toBe($expectedTotal);
});

test('benchmark scheduler fixed events are on correct dates', function () {
    Carbon::setTestNow('2026-03-16');

    $scheduler = buildBenchmarkScheduler();
    $schedule = $scheduler->getSchedule();

    $fixedPlanned = $schedule->getAllEvents()
        ->filter(fn ($pe) => $pe->getEvent() instanceof FixedEvent);

    expect($fixedPlanned)->toHaveCount(50);

    foreach ($fixedPlanned as $pe) {
        /** @var FixedEvent $event */
        $event = $pe->getEvent();
        expect($pe->getBlock()->getDateTime()->toDateString())
            ->toBe($event->getStart()->toDateString());
    }
});

test('benchmark scheduler skips weekends', function () {
    Carbon::setTestNow('2026-03-16');

    $scheduler = buildBenchmarkScheduler();
    $schedule = $scheduler->getSchedule();

    foreach ($schedule->getBlocks() as $block) {
        expect($block->getDateTime()->isWeekend())->toBeFalse();
    }
});

test('benchmark scheduler detects problematic events', function () {
    Carbon::setTestNow('2026-03-16');

    $scheduler = buildBenchmarkScheduler();
    $schedule = $scheduler->getSchedule();

    // getProblematicEvents should return a collection (may be empty or not)
    $problematic = $schedule->getProblematicEvents();
    expect($problematic)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

// ── Timing baseline ──

test('benchmark: execution timing baseline', function () {
    $times = runBenchmark(20);

    $avg = array_sum($times) / count($times);
    $min = min($times);
    $max = max($times);

    sort($times);
    $p50 = $times[(int) floor(count($times) * 0.5)];
    $p95 = $times[(int) floor(count($times) * 0.95)];

    echo "\n";
    echo "  ┌─────────────────────────────────────┐\n";
    echo "  │     BENCHMARK BASELINE (20 runs)     │\n";
    echo "  ├─────────────────────────────────────┤\n";
    echo sprintf("  │  avg:  %8.2f ms                   │\n", $avg);
    echo sprintf("  │  min:  %8.2f ms                   │\n", $min);
    echo sprintf("  │  max:  %8.2f ms                   │\n", $max);
    echo sprintf("  │  p50:  %8.2f ms                   │\n", $p50);
    echo sprintf("  │  p95:  %8.2f ms                   │\n", $p95);
    echo "  └─────────────────────────────────────┘\n";

    expect($avg)->toBeGreaterThan(0);
});
