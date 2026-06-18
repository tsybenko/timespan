<?php

namespace Tsybenko\TimeSpan;

class Timeline
{
    protected int $start = 0;
    protected int $end = 0;

    /** @var Span[] */
    protected array $spans = [];

    /**
     * Returns starting timestamp of the timeline.
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Returns ending timestamp of the timeline.
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * Returns spans of the timeline.
     *
     * @return Span[]
     */
    public function getSpans(): array
    {
        return $this->spans;
    }

    /**
     * Adds a span on the timeline.
     *
     * @return Span[]
     */
    public function add(Span $span)
    {
        $this->spans[] = $span;

        if (0 === $this->start || $this->start > $span->getStart()) {
            $this->start = $span->getStart();
        }

        if (0 === $this->end || $this->end < $span->getEnd()) {
            $this->end = $span->getEnd();
        }

        return $this->spans = SpanAggregator::sort($this->spans);
    }

    /**
     * Returns duration of the timeline.
     */
    public function getDuration(): int
    {
        return $this->end - $this->start;
    }

    /**
     * Returns array of merged spans of passed timelines.
     *
     * @param static ...$timelines
     *
     * @return Span[]
     */
    public static function mergeSpans(self ...$timelines): array
    {
        /** @var Span[] $spans */
        $spans = [];

        foreach ($timelines as $timeline) {
            $spans = [...$spans, ...$timeline->getSpans()];
        }

        return $spans;
    }

    /**
     * Returns a new Timeline by merging current timeline with passed timelines.
     */
    public function merge(self ...$timelines): self
    {
        $timeline = new self();
        $spans = self::mergeSpans(...$timelines);

        foreach ($spans as $span) {
            $timeline->add($span);
        }

        return $timeline;
    }

    /**
     * Returns a new Timeline pre-filled with generated spans, starting from $start.
     */
    public static function generateFrom(
        int $start,
        int $duration,
        int $amount = 1,
        int $gap = 0,
    ): self {
        $timeline = new self();

        for ($i = 0; $i < $amount; ++$i) {
            $span = new Span($start, $start + $duration);
            $timeline->add($span);
            $start += $duration + $gap;
        }

        return $timeline;
    }

    /**
     * Returns a new Timeline pre-filled with generated spans, ending from $end.
     */
    public static function generateTo(
        int $end,
        int $duration,
        int $amount = 1,
        int $gap = 0,
    ): self {
        $timeline = new self();

        for ($i = $amount; $i > 0; --$i) {
            $span = new Span($end - $duration, $end);
            $timeline->add($span);
            $end -= $duration + $gap;
        }

        return $timeline;
    }

    /**
     * Resets start and end of the timeline.
     */
    protected function resetStartAndEnd(): void
    {
        foreach ($this->spans as $span) {
            if (0 === $this->start || $this->start > $span->getStart()) {
                $this->start = $span->getStart();
            }
            if (0 === $this->end || $this->end < $span->getEnd()) {
                $this->end = $span->getEnd();
            }
        }
    }
}
