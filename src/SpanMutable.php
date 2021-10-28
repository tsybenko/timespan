<?php

namespace Tsybenko\TimeSpan;

class SpanMutable extends Span
{
    /**
     * @inheritDoc
     */
    public function merge(AbstractSpan ...$spans): static
    {
        $span = parent::merge(...$spans);

        $this->start = $span->getStart();
        $this->end = $span->getEnd();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function offset(int $size): static
    {
        $this->start += $size;
        $this->end += $size;

        return $this;
    }
}