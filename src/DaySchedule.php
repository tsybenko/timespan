<?php

namespace Tsybenko\TimeSpan;

class DaySchedule extends Span implements ManagesEvents
{
    /** @var Event[] */
    protected array $events;

    public function addEvent(Event $event)
    {
        $this->events[] = $event;
        $this->events = Span::sort($this->events);
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @return Gap[]
     */
    public function gaps(): array
    {
        $gaps = [];

        for ($i = 0; $i < count($this->events) - 1; $i++) {
            $event = $this->events[$i];
            $nextEvent = $this->events[$i + 1];

            if (empty($nextEvent)) break;

            $gap = $event->gap($nextEvent);

            if ($gap === 0) continue;

            $gaps[] = new Span($event->getEnd(), $nextEvent->getStart());
        }

        return $gaps;
    }

    public function getFreeTime(): array
    {
        $gaps = $this->gaps();

        if (empty($this->events)) return $gaps;

        $firstEvent = $this->events[array_key_first($this->events)];
        $lastEvent = $this->events[array_key_last($this->events)];

        if ($firstEvent === $lastEvent) return $gaps;

        if ($this->start < $firstEvent->getStart()) {
            $gaps[] = new Span($this->start, $firstEvent->getStart());
        }

        if ($this->end > $lastEvent->getEnd()) {
            $gaps[] = new Span($lastEvent->getEnd(), $this->end);
        }

        return Span::sort($gaps);
    }

    public function closestFreeTime(string $datetime): Span
    {
        $spans = $this->getFreeTime();

        $spans = array_filter($spans, function ($span) use ($datetime) {
            return $span->getStart() >= strtotime($datetime);
        });

        $spans = Span::sort($spans);

        return $spans[array_key_first($spans)];
    }
}