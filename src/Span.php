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
        $start = (new DateTimeImmutable())->setTimestamp($this->start);
        $end = (new DateTimeImmutable())->setTimestamp($this->end);

        return new DatePeriod($start, $interval, $end);
    }

    /**
     * Returns array representation of the span
     *
     * @return array{start: int, end: int}
     */
    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    /**
     * Returns array of main primitives: start, end
     *
     * @return array{0: int, 1: int}
     */
    public function toPrimitives(): array
    {
        return [$this->start, $this->end];
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

    public function getMiddle(): int
    {
        return ($this->start + $this->end) / 2;
    }

    /**
     * Returns duration of one fraction after dividing the span in $fractions count
     *
     * @param int $fractions
     * @return float
     */
    public function getFractionDuration(int $fractions): float
    {
        if ($fractions < 0) {
            throw new InvalidArgumentException('Fractions amount must be an unsigned integer');
        }

        return $this->getDuration() / $fractions;
    }

    public function gap(SpanInterface $span): int
    {
        if ($this->start === $span->getStart() && $this->end === $span->getEnd()) {
            return 0;
        }

        if ($this->overlaps($span)) {
            return 0;
        }

        return $this->start > $span->getStart()
            ? $this->start - $span->getEnd()
            : $span->getStart() - $this->end;
    }

    public function hasGap(SpanInterface $span): bool
    {
        return $this->gap($span) > 0;
    }

    public function overlaps(SpanInterface $span): bool
    {
        return $this->start < $span->getStart()
            ? $span->getStart() <= $this->end
            : $this->start <= $span->getEnd();
    }

    /**
     * @param int $timestamp
     * @return bool
     */
    public function contains(int $timestamp): bool
    {
        return $this->start <= $timestamp && $this->end >= $timestamp;
    }

    /**
     * @param int $timestamp
     * @return Span[]
     */
    public function splitTimestamp(int $timestamp): array
    {
        if (! $this->contains($timestamp)) {
            throw new InvalidArgumentException("The span does not contain the passed timestamp");
        }

        return [
            static::make($this->start, $timestamp),
            static::make($timestamp, $this->end)
        ];
    }

    /**
     * Returns array of parts (spans) the span was splitted into
     *
     * Warning!
     * Splitting of the span into odd amount of parts is not an accurate operation
     * because of float to integer type conversion
     *
     * @param int $count
     * @return Span[]
     */
    public function splitParts(int $count): array
    {
        if ($count < 2) {
            throw new InvalidArgumentException('Cannot split a span into less than 2 parts');
        }

        $start = $this->start;
        $fractionDuration = $this->getFractionDuration($count);

        $parts = [];

        while (count($parts) < $count) {
            $end = $start + $fractionDuration;
            $parts[] = static::make($start, $end);
            $start = $end;
        }

        return $parts;
    }

    /**
     * Returns a new span as a result of the merging of current and passed spans
     *
     * @param Span ...$spans
     * @return Span
     */
    public function merge(self ...$spans): static
    {
        if (empty($spans)) {
            throw new InvalidArgumentException('Must be at least 1 item passed');
        }

        $start = 0;
        $end = 0;

        foreach ($spans as $span) {
            $start = min($this->start, $span->getStart());
            $end = max($this->end, $span->getEnd());
        }

        return static::make($start, $end);
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

    public function __serialize(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->start = $data['start'];
        $this->end = $data['end'];
    }
}