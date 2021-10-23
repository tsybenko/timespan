<?php

namespace Tsybenko\TimeSpan;

class Timeline
{
    protected int $start = 0;
    protected int $end = 0;

    /** @var Span[] */
    protected array $spans = [];

    /**
     * Returns starting timestamp of the timeline
     *
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Returns ending timestamp of the timeline
     *
     * @return int
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * Returns spans of the timeline
     *
     * @return Span[]
     */
    public function getSpans(): array
    {
        return $this->spans;
    }

    /**
     * Adds a span on the timeline
     *
     * @param Span $span
     * @return Span[]
     */
    public function add(Span $span)
    {
        $this->spans[] = $span;

        if ($this->start === 0 || $this->start > $span->getStart()) {
            $this->start = $span->getStart();
        }

        if ($this->end === 0 || $this->end < $span->getEnd()) {
            $this->end = $span->getEnd();
        }

        return $this->spans = SpanAggregator::sort($this->spans);
    }

    /**
     * Returns duration of the timeline
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->end - $this->start;
    }

    /**
     * Resets start and end of the timeline
     */
    protected function resetStartAndEnd(): void
    {
        foreach ($this->spans as $span) {
            if ($this->start === 0 || $this->start > $span->getStart()) {
                $this->start = $span->getStart();
            }
            if ($this->end === 0 || $this->end < $span->getEnd()) {
                $this->end = $span->getEnd();
            }
        }
    }

    /**
     * Returns array of merged spans of passed timelines
     *
     * @param static ...$timelines
     * @return Span[]
     */
    public static function mergeSpans(self ...$timelines): array
    {
        /** @var Span[] $spans */
        $spans = [];

        foreach ($timelines as $timeline) {
            $spans += $timeline->getSpans();
        }

        return $spans;
    }

    /**
     * Returns a new Timeline by merging current timeline with passed timelines.
     *
     * @param Timeline ...$timelines
     * @return static
     */
    public function merge(self ...$timelines): static
    {
        $composition = new static();

        foreach ($timelines as $timeline) {
            foreach (static::mergeSpans($timeline) as $span) {
                $composition->add($span);
            }
        }

        $composition->spans = SpanAggregator::sort($composition->spans);

        $composition->resetStartAndEnd();

        return $composition;
    }

    /**
     * Returns a new Timeline pre-filled with generated spans, starting from $start
     *
     * @param int $start
     * @param int $duration
     * @param int $amount
     * @param int $gap
     * @return static
     */
    public static function generateFrom(int $start, int $duration, int $amount = 1, int $gap = 0): static
    {
        $timeline = new static();

        for ($i = 0; $i < $amount; $i++) {
            $span = new Span($start, $start + $duration);
            $timeline->add($span);
            $start += $duration + $gap;
        }

        return $timeline;
    }

    /**
     * Returns a new Timeline pre-filled with generated spans, ending from $end
     *
     * @param int $end
     * @param int $duration
     * @param int $amount
     * @param int $gap
     * @return static
     */
    public static function generateTo(int $end, int $duration, int $amount = 1, int $gap = 0): static
    {
        $timeline = new static();

        for ($i = $amount; $i > 0; $i--) {
            $span = new Span($end - $duration, $end);
            $timeline->add($span);
            $end -= $duration + $gap;
        }

        return $timeline;
    }
}