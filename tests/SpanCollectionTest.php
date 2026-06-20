<?php

use PHPUnit\Framework\TestCase;
use Tsybenko\TimeSpan\Span;
use Tsybenko\TimeSpan\SpanCollection;

/**
 * @internal
 *
 * @coversNothing
 */
class SpanCollectionTest extends TestCase
{
    public function testCanCreateEmptySpanCollection(): void
    {
        $spanCollection = new SpanCollection();

        $this->assertEmpty($spanCollection);
    }

    public function testCanCreateSpanCollectionWithSpans(): void
    {
        $span = new Span(1, 2);
        $spanCollection = new SpanCollection([$span]);

        $this->assertNotEmpty($spanCollection->getAll());
        $this->assertContains($span, $spanCollection->getAll());
    }

    public function testCanAppendSpanToCollection(): void
    {
        $spanCollection = new SpanCollection();

        $span1 = new Span(1, 2);
        $span2 = new Span(2, 3);

        $spanCollection->append($span1);
        $spanCollection->append($span2);

        $this->assertNotEmpty($spanCollection->getAll());
        $this->assertContains($span1, $spanCollection->getAll());
        $this->assertContains($span2, $spanCollection->getAll());
    }

    public function testCanCountSpans(): void
    {
        $spanCollection = new SpanCollection([
            new Span(1, 2),
            new Span(2, 3),
        ]);

        $this->assertEquals(2, $spanCollection->count());
        $this->assertCount(2, $spanCollection);
    }

    public function testCanRetrieveMostDurableSpan(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(2, 4);
        $span3 = new Span(4, 8);

        $spanCollection = new SpanCollection([$span1, $span2, $span3]);

        $mostDurableSpan = $spanCollection->mostDurable();

        $this->assertNotNull($mostDurableSpan);
        $this->assertSame($span3, $mostDurableSpan);
    }

    public function testCanRetrieveLessDurableSpan(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(2, 4);
        $span3 = new Span(4, 8);

        $spanCollection = new SpanCollection([$span1, $span2, $span3]);

        $mostDurableSpan = $spanCollection->lessDurable();

        $this->assertNotNull($mostDurableSpan);
        $this->assertSame($span1, $mostDurableSpan);
    }

    public function testMostDurableReturnsNullWhenCollectionIsEmpty(): void
    {
        $spanCollection = new SpanCollection();

        $mostDurableSpan = $spanCollection->mostDurable();

        $this->assertNull($mostDurableSpan);
    }

    public function testLessDurableReturnsNullWhenCollectionIsEmpty(): void
    {
        $spanCollection = new SpanCollection();

        $lessDurableSpan = $spanCollection->lessDurable();

        $this->assertNull($lessDurableSpan);
    }

    public function testCanRetrieveSortedCollectionWithDefaultAscendingSortingOrder(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(2, 4);
        $span3 = new Span(4, 8);
        $span4 = new Span(8, 16);

        $spanCollection = new SpanCollection([$span3, $span1, $span4, $span2]);

        $sortedSpanCollection = $spanCollection->sorted();

        $this->assertNotEquals($spanCollection, $sortedSpanCollection);
        $this->assertEquals($span1, $sortedSpanCollection->offsetGet(0));
        $this->assertEquals($span2, $sortedSpanCollection->offsetGet(1));
        $this->assertEquals($span3, $sortedSpanCollection->offsetGet(2));
        $this->assertEquals($span4, $sortedSpanCollection->offsetGet(3));
    }

    public function testCanRetrieveSortedCollectionWithDescendingSortingOrder(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(2, 4);
        $span3 = new Span(4, 8);
        $span4 = new Span(8, 16);

        $spanCollection = new SpanCollection([$span3, $span1, $span4, $span2]);

        $sortedSpanCollection = $spanCollection->sorted(
            static fn ($a, $b) => $b <=> $a,
        );

        $this->assertNotEquals($spanCollection, $sortedSpanCollection);
        $this->assertEquals($span4, $sortedSpanCollection->offsetGet(0));
        $this->assertEquals($span3, $sortedSpanCollection->offsetGet(1));
        $this->assertEquals($span2, $sortedSpanCollection->offsetGet(2));
        $this->assertEquals($span1, $sortedSpanCollection->offsetGet(3));
    }

    /**
     * @dataProvider provideSpanToCalculateDurationsSum
     */
    public function testCanSumDurations(array $spans, int $expectedDurationsSum): void
    {
        $spanCollection = new SpanCollection($spans);

        $sumDurations = $spanCollection->sumDurations();

        $this->assertEquals($expectedDurationsSum, $sumDurations);
    }

    protected function provideSpanToCalculateDurationsSum(): array
    {
        return [
            [
                [
                    new Span(1, 2),
                    new Span(2, 3),
                    new Span(3, 4),
                ],
                3,
            ],
            [
                [
                    new Span(1, 5),
                    new Span(5, 10),
                    new Span(10, 15),
                ],
                14,
            ],
            [
                [
                    new Span(1, 5),
                    new Span(5, 125),
                    new Span(125, 325),
                ],
                324,
            ],
        ];
    }

    public function testReturnsEmptyGapsArrayWhenNoGapsBetweenSpans(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(2, 3);

        $spanCollection = new SpanCollection([$span1, $span2]);

        $gaps = $spanCollection->gaps();

        $this->assertEmpty($gaps);
    }

    public function testReturnsEmptyGapsArrayWhenOnlyOneSpanInCollection(): void
    {
        $span1 = new Span(1, 2);

        $spanCollection = new SpanCollection([$span1]);

        $gaps = $spanCollection->gaps();

        $this->assertEmpty($gaps);
    }

    public function testCanRetrieveGapsBetweenSpans(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(4, 6);
        $span3 = new Span(10, 12);

        $spanCollection = new SpanCollection([$span1, $span2, $span3]);

        $gaps = $spanCollection->gaps();

        $this->assertNotEmpty($gaps);
        $this->assertEquals(2, $gaps[0]);
        $this->assertEquals(4, $gaps[1]);
    }

    public function testCanRetrieveSumOfGapsBetweenSpansInCollection(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(4, 6);
        $span3 = new Span(10, 12);

        $spanCollection = new SpanCollection([$span1, $span2, $span3]);

        $gapsSum = $spanCollection->sumGaps();

        $this->assertNotEmpty($gapsSum);
        $this->assertEquals(6, $gapsSum);
    }

    public function testCanIterateOverGaps(): void
    {
        $span1 = new Span(1, 2);
        $span2 = new Span(4, 6);
        $span3 = new Span(10, 12);

        $spanCollection = new SpanCollection([$span1, $span2, $span3]);

        $gapsGenerator = $spanCollection->iterateGaps();

        $this->assertIsIterable($gapsGenerator);
    }
}
