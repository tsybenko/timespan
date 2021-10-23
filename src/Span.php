<?php

namespace Tsybenko\TimeSpan;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;

class Span implements SpanInterface
{
    protected int $start;
    protected int $end;

    public function __construct(int $start, int $end)
    {
        if ($start > $end) {
            throw new InvalidArgumentException('The "end" value cannot be less than the "start" value');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public static function make(int $start, int $end): static
    {
        return new static($start, $end);
    }

    public static function fromDateTime(DateTimeInterface $start, DateTimeInterface $end): static
    {
        return new static($start->getTimestamp(), $end->getTimestamp());
    }

    /**
     * @throws Exception
     */
    public function toDatePeriod(DateInterval $interval): DatePeriod
    {
        $start = new DateTimeImmutable(strtotime($this->start));
        $end = new DateTimeImmutable(strtotime($this->end));

        return new DatePeriod($start, $interval, $end);
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toString(): string
    {
        return sprintf(
            '%s -> %s',
            date('H:i d.m.Y', $this->start),
            date('H:i d.m.Y', $this->end)
        );
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getDuration(): int
    {
        return $this->end - $this->start;
    }

    public function gap(SpanInterface $span): int
    {
        if ($this->start === $span->start && $this->end === $span->end) {
            return 0;
        }

        if ($this->overlaps($span)) {
            return 0;
        }

        return $this->start > $span->start
            ? $this->start - $span->end
            : $span->start - $this->end;
    }

    public function hasGap(SpanInterface $span): bool
    {
        return $this->gap($span) > 0;
    }

    public function overlaps(SpanInterface $span): bool
    {
        return $this->start < $span->start
            ? $span->start <= $this->end
            : $this->start <= $span->end;
    }

    public function startsAfter(int $timestamp): bool
    {
        return $this->start > $timestamp;
    }

    public function startsBefore(int $timestamp): bool
    {
        return $this->start < $timestamp;
    }

    public function endsAfter(int $timestamp): bool
    {
        return $this->end > $timestamp;
    }

    public function endsBefore(int $timestamp): bool
    {
        return $this->end < $timestamp;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}