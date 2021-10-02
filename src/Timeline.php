<?php

namespace Tsybenko\TimeSpan;

class Timeline
{
    protected int $start = 0;
    protected int $end = 0;

    /** @var Span[] */
    protected array $spans = [];

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
}