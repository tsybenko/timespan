<?php

namespace Tsybenko\TimeSpan;

use Generator;

class SpanAggregator
{
    protected Span $span;

    public function __construct(Span $span)
    {
        $this->span = $span;
    }

    /**
     * @param Span[] $spans
     * @return Span[]
     */
    public static function sort(array $spans): array
    {
        $spans = array_merge([], $spans);

        uasort($spans, function (Span $a, Span $b) {
            return $a->getStart() <=> $b->getStart();
        });

        return $spans;
    }

    public function iterateGaps(Span ...$spans): Generator
    {
        foreach ($spans as $span) {
            yield $this->span->gap($span);
        }
    }

    public static function sumDurations(Span ...$spans): int
    {
        return array_reduce($spans, function($sum, Span $span) {
            $sum += $span->getDuration();
            return $sum;
        }, 0);
    }

    public static function sumGaps(Span ...$spans): int
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
    public static function findBetween(Span $a, Span $b, array $spans): array
    {
        $composition = new Span($a->getStart(), $b->getStart());

        return array_filter($spans, function ($span) use ($composition) {
            return $composition->overlaps($span);
        });
    }

    public static function findMostDurable(Span ...$spans): Span
    {
        $candidate = $spans[0];

        if (count($spans) === 1) return $candidate;

        // Iterate from the next index for better performance
        for ($i = 1; $i < count($spans); $i++) {
            $span = $spans[$i];

            if ($candidate->getDuration() > $span->getDuration()) continue;

            $candidate = $span;
        }

        return $candidate;
    }

}