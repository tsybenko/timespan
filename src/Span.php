<?php

namespace Tsybenko\TimeSpan;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Generator;
use InvalidArgumentException;

class Span
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

    public function toString()
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

    /**
     * @param Span[] $spans
     * @return Span[]
     */
    public static function sort(array $spans): array
    {
        $spans = array_merge([], $spans);

        uasort($spans, function ($a, $b) {
            /** @var Span $a */
            /** @var Span $b */
            return $a->getStart() <=> $b->getStart();
        });

        return $spans;
    }

    public function duration(): int
    {
        return $this->end - $this->start;
    }

    public function gap(self $span): int
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

    public function hasGap(self $span): bool
    {
        return $this->gap($span) > 0;
    }

    public function iterateGaps(self ...$spans): Generator
    {
        foreach ($spans as $span) {
            yield $this->gap($span);
        }
    }

    public function overlaps(self $span): bool
    {
        return $this->start < $span->start
            ? $span->start <= $this->end
            : $this->start <= $span->end;
    }

    public static function sumDurations(self ...$spans): int
    {
        return array_reduce($spans, function($sum, $span) {
            $sum += $span->duration();
            return $sum;
        }, 0);
    }

    public static function sumGaps(self ...$spans): int
    {
        $sum = 0;

        for ($i = 0; $i < count($spans) - 1; $i++) {
            if (! isset($spans[$i + 1])) break;

            $currentSpan = $spans[$i];
            $nextSpan = $spans[$i + 1];

            $sum += $currentSpan->gap($nextSpan);
        }

        return $sum;
    }

    /**
     * @param Span[] $spans
     * @return Span[]
     */
    public static function findBetween(self $a, self $b, array $spans): array
    {
        $composition = new static($a->getStart(), $b->getStart());

        return array_filter($spans, function ($span) use ($composition) {
            return $composition->overlaps($span);
        });
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}