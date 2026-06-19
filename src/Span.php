<?php

namespace Tsybenko\TimeSpan;

class Span implements HasStart, HasEnd, HasDuration, \Stringable
{
    protected int $start;
    protected int $end;

    public function __construct(int $start, int $end)
    {
        if ($start > $end) {
            throw new \InvalidArgumentException(
                'The "end" value cannot be less than the "start" value',
            );
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return array{start: int, end: int}
     */
    public function __serialize(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    /**
     * @param array{start: int, end: int} $data
     */
    public function __unserialize(array $data): void
    {
        $this->start = $data['start'];
        $this->end = $data['end'];
    }

    public static function fromTimestamp(int $start, int $end): self
    {
        return new self($start, $end);
    }

    public static function fromDateTime(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): self {
        return new self($start->getTimestamp(), $end->getTimestamp());
    }

    public function cloneWithOffset(int $size): self
    {
        return static::fromTimestamp($this->start + $size, $this->end + $size);
    }

    /**
     * @throws \Exception
     */
    public function toDatePeriod(\DateInterval $interval): \DatePeriod
    {
        $start = new \DateTimeImmutable()->setTimestamp($this->start);
        $end = new \DateTimeImmutable()->setTimestamp($this->end);

        return new \DatePeriod($start, $interval, $end);
    }

    /**
     * Returns array representation of the span.
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
     * Returns array of main primitives: start, end.
     *
     * @return array{0: int, 1: int}
     */
    public function toScalarArray(): array
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
            date('H:i d.m.Y', $this->end),
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
     * Returns duration of one fraction after dividing the span in $fractions count.
     */
    public function getFractionDuration(int $fractions): float
    {
        if ($fractions < 0) {
            throw new \InvalidArgumentException(
                'Fractions amount must be a positive number',
            );
        }

        return $this->getDuration() / $fractions;
    }

    public function gap(HasEnd&HasStart $other): int
    {
        if (
            $this->start === $other->getStart()
            && $this->end === $other->getEnd()
        ) {
            return 0;
        }

        if ($this->overlaps($other)) {
            return 0;
        }

        return $this->start > $other->getStart()
            ? $this->start - $other->getEnd()
            : $other->getStart() - $this->end;
    }

    public function hasGap(HasEnd&HasStart $other): bool
    {
        return $this->gap($other) > 0;
    }

    public function overlaps(HasEnd&HasStart $other): bool
    {
        return $this->start < $other->getStart()
            ? $other->getStart() <= $this->end
            : $this->start <= $other->getEnd();
    }

    public function contains(int $timestamp): bool
    {
        return $this->start <= $timestamp && $this->end >= $timestamp;
    }

    /**
     * @return Span[]
     */
    public function splitByTimestamp(int $timestamp): array
    {
        if (!$this->contains($timestamp)) {
            throw new \InvalidArgumentException(
                'The span does not contain the passed timestamp',
            );
        }

        return [
            self::fromTimestamp($this->start, $timestamp),
            self::fromTimestamp($timestamp, $this->end),
        ];
    }

    /**
     * Returns array of parts (spans) the span was splitted into.
     *
     * Warning!
     * Splitting of the span into odd amount of parts is not an accurate operation
     * because of float to integer type conversion
     *
     * @return Span[]
     */
    public function splitParts(int $count): array
    {
        if ($count < 2) {
            throw new \InvalidArgumentException(
                'Cannot split a span into less than 2 parts',
            );
        }

        $start = $this->start;
        $fractionDuration = $this->getFractionDuration($count);

        $parts = [];

        while (count($parts) < $count) {
            $end = $start + $fractionDuration;
            $parts[] = self::fromTimestamp($start, $end);
            $start = $end;
        }

        return $parts;
    }

    /**
     * Returns a new span as a result of the merging of current and passed spans.
     *
     * @return Span
     */
    public function merge(self ...$spans): static
    {
        if (empty($spans)) {
            throw new \InvalidArgumentException(
                'Must be at least 1 item passed',
            );
        }

        $start = 0;
        $end = 0;

        foreach ($spans as $span) {
            $start = min($this->start, $span->getStart());
            $end = max($this->end, $span->getEnd());
        }

        return static::fromTimestamp($start, $end);
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
}
