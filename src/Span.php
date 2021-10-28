<?php

namespace Tsybenko\TimeSpan;

use InvalidArgumentException;

class Span extends AbstractSpan implements SpanInterface
{
    /**
     * @inheritDoc
     */
    public function merge(AbstractSpan ...$spans): static
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

    /**
     * @inheritDoc
     */
    public function offset(int $size): static
    {
        return static::make(
            $this->start + $size,
            $this->end + $size
        );
    }
}