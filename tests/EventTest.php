<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tsybenko\TimeSpan\Event;

class EventTest extends TestCase
{

    public function testCanBeCreatedFromAllDay()
    {
        $event = Event::allDayLong('today');

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(86400, $event->duration());
    }
}