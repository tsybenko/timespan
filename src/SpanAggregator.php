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
}