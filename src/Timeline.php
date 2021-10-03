<?php

namespace Tsybenko\TimeSpan;

class Timeline
{
    protected int $start = 0;
    protected int $end = 0;

    /** @var Span[] */
    protected array $spans = [];

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * @return Span[]
     */
    public function getSpans(): array
    {
        return $this->spans;
    }

    public function add(Span $span)
    {
        $this->spans[] = $span;

        if ($this->start > $span->getStart()) {
            $this->start = $span->getStart();
        }

        if ($this->end < $span->getEnd()) {
            $this->end = $span->getEnd();
        }

        uasort($this->spans, function ($a, $b) {
            return $a->getStart() <=> $b->getStart();
        });
    }

    public function duration(): int
    {
        return $this->end - $this->start;
    }

    protected function resetStartAndEnd(): void
    {
        foreach ($this->spans as $span) {
            if ($this->start > $span->getStart()) {
                $this->start = $span->getStart();
            }
            if ($this->end < $span->getEnd()) {
                $this->end = $span->getEnd();
            }
        }
    }

    /**
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

    public function merge(self ...$timelines): static
    {
        $composition = new static();

        foreach ($timelines as $timeline) {
            $composition->spans = static::mergeSpans($timeline);
        }

        $composition->spans = Span::sort($composition->spans);

        $composition->resetStartAndEnd();

        return $composition;
    }
}