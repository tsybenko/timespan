<?php

namespace Tsybenko\TimeSpan;

class SpanCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @param Span[] $spans
     */
    public function __construct(
        protected array $spans = []
    ) {}

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->spans[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->spans[$offset] = $value;
    }

    public function offsetGet(mixed $offset): Span
    {
        return $this->spans[$offset];
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->spans[$offset]);
    }

    public function count(): int
    {
        return count($this->spans);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->spans);
    }

    public function append(Span $span): void
    {
        $this->spans[] = $span;
    }

    /**
     * @return Span[]
     */
    public function getAll(): array
    {
        return $this->spans;
    }

    public function sorted(?\Closure $compare = null): self
    {
        static $defaultCompare = static fn ($a, $b) => $a <=> $b;

        $spans = array_merge([], $this->spans);

        usort($spans, $compare ?? $defaultCompare);

        return new self($spans);
    }

    public function sumDurations(): int
    {
        return array_reduce($this->spans, function ($sum, Span $span) {
            $sum += $span->getDuration();

            return $sum;
        }, 0);
    }

    public function sumGaps(): int
    {
        $sum = 0;

        foreach ($this->iterateGaps() as $gap) {
            $sum += $gap;
        }

        return $sum;
    }

    /**
     * @return int[]
     */
    public function gaps(): array
    {
        return iterator_to_array($this->iterateGaps());
    }

    /**
     * @return \Generator<int>
     */
    public function iterateGaps(): \Generator
    {
        $spanCount = count($this->spans);

        if (1 === $spanCount) {
            return;
        }

        for ($i = 0; $i < $spanCount; ++$i) {
            if (!isset($this->spans[$i + 1])) {
                return;
            }

            $gap = $this->spans[$i]->gap($this->spans[$i + 1]);

            if (0 !== $gap) {
                yield $gap;
            }
        }
    }

    public function mostDurable(): ?Span
    {
        return $this->findByDuration(
            static fn (Span $candidate, Span $other) => $candidate->getStart() < $other->getStart(),
        );
    }

    public function lessDurable(): ?Span
    {
        return $this->findByDuration(
            static fn (Span $candidate, Span $other) => $candidate->getStart() > $other->getStart(),
        );
    }

    protected function findByDuration(\Closure $condition): ?Span
    {
        if (!$this->spans) {
            return null;
        }

        $spanCount = count($this->spans);
        $candidate = $this->spans[0];

        if (1 === $spanCount) {
            return $candidate;
        }

        // Iterate from the next index for better performance
        for ($i = 1; $i < $spanCount; ++$i) {
            $span = $this->spans[$i];

            if ($condition($candidate, $span)) {
                $candidate = $span;
            }
        }

        return $candidate;
    }
}
