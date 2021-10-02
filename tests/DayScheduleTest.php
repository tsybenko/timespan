<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tsybenko\TimeSpan\Event;
use Tsybenko\TimeSpan\DaySchedule;
use Tsybenko\TimeSpan\Span;

class DayScheduleTest extends TestCase
{
    public function testShouldFindGapBetweenTwoEvents()
    {
        $schedule = DaySchedule::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('17:00')
        );

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        ));

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('12:00'),
            new DateTimeImmutable('13:00')
        ));

        $this->assertCount(1, $schedule->gaps());
    }

    public function testShouldFindAllFreeTime()
    {
        $schedule = DaySchedule::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('17:00')
        );

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        ));

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('12:00'),
            new DateTimeImmutable('13:00')
        ));

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('13:00'),
            new DateTimeImmutable('15:00')
        ));

        $this->assertCount(2, $schedule->getFreeTime());
    }

    public function testShouldFindClosestFreeTime()
    {
        $datetime = 'today 11:00';

        $schedule = DaySchedule::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('17:00')
        );

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        ));

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('12:00'),
            new DateTimeImmutable('13:00')
        ));

        $schedule->addEvent(Event::fromDateTime(
            new DateTimeImmutable('13:00'),
            new DateTimeImmutable('15:00')
        ));

        $span = $schedule->closestFreeTime($datetime);
        $this->assertInstanceOf(Span::class, $span);
        $this->assertSame('11:00', date('H:i', $span->getStart()));
    }
}