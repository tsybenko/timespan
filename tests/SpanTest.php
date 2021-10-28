<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tsybenko\TimeSpan\Span;

class SpanTest extends TestCase
{
    public function getTimestamp(): int
    {
        return 1635368400;
    }

    public function provideDateTime()
    {
        return [
            [
                new DateTimeImmutable('09:00'),
                new DateTimeImmutable('11:00')
            ]
        ];
    }

    /**
     * @return Span[]
     */
    public function provideSpan(): array
    {
//        $ts = $this->getTimestamp();
//        return Span::make($ts, $ts + 7200);

        $start = new DateTimeImmutable('09:00');
        $end = new DateTimeImmutable('11:00');

        return [
            [Span::fromDateTime($start, $end)]
        ];
    }

    public function testThrowsExceptionWhenEndLessThanStart()
    {
        $this->expectException(InvalidArgumentException::class);

        $start = 100;
        $end = 50;

        new Span($start, $end);
    }

    /**
     * @dataProvider provideDateTime
     */
    public function testCanBeCreatedFromTimestamp($start, $end)
    {
        $this->assertInstanceOf(
            Span::class,
            new Span(
                $start->getTimestamp(),
                $end->getTimestamp()
            )
        );
    }

    /**
     * @dataProvider provideDateTime
     */
    public function testCanBeCreatedFromStaticConstructor($start, $end)
    {
        $this->assertInstanceOf(
            Span::class,
            Span::make(
                $start->getTimestamp(),
                $end->getTimestamp()
            )
        );
    }

    /**
     * @dataProvider provideDateTime
     */
    public function testCanBeCreatedFromDatetime($start, $end)
    {
        $this->assertInstanceOf(
            Span::class,
            Span::fromDateTime($start, $end)
        );
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     * @dataProvider provideSpan
     */
    public function testCanCalculateDuration($span)
    {
        $this->assertSame(7200, $span->getDuration());
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     * @dataProvider provideSpan
     */
    public function testCanBeConvertedToDatePeriod($span)
    {
        $interval = new DateInterval('PT1H');

        $this->assertInstanceOf(DatePeriod::class, $span->toDatePeriod($interval));
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     * @dataProvider provideSpan
     */
    public function testCanBeConvertedToArray($span)
    {
        $arr = $span->toArray();

        $this->assertIsArray($span->toArray());
        $this->assertArrayHasKey('start', $arr);
        $this->assertArrayHasKey('end', $arr);
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     * @dataProvider provideSpan
     */
    public function testCanBeConvertedToJson($span)
    {
        $this->assertJson($span->toJson());
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     */
    public function testCanDetectOverlapping()
    {
        /** @var Span[] $spans */
        $spans = [
            Span::fromDateTime(
                new DateTimeImmutable('09:00'),
                new DateTimeImmutable('11:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('10:00'),
                new DateTimeImmutable('12:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('13:00'),
                new DateTimeImmutable('15:00')
            ),
        ];

        $this->assertTrue($spans[0]->overlaps($spans[1]));
        $this->assertTrue($spans[1]->overlaps($spans[0]));

        $this->assertFalse($spans[0]->overlaps($spans[2]));
        $this->assertFalse($spans[2]->overlaps($spans[0]));
    }

    public function testCanCalculateGapBetweenTwoSpans()
    {
        $spanA = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

        $spanB = Span::fromDateTime(
            new DateTimeImmutable('12:00'),
            new DateTimeImmutable('13:00')
        );

        $this->assertSame(3600, $spanA->gap($spanB));
    }

    public function testGapBetweenTwoSpansReturnsZeroIfSpansAreOverlap()
    {
        $spanA = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('13:00')
        );

        $spanB = Span::fromDateTime(
            new DateTimeImmutable('10:00'),
            new DateTimeImmutable('13:00')
        );

        $this->assertSame(0, $spanB->gap($spanA));
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanCheckWhetherSpanStartsAfterTimestamp($span)
    {
        $datetime = new DateTimeImmutable('08:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->startsAfter($timestamp));
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanCheckWhetherSpanStartsBeforeTimestamp($span)
    {
        $datetime = new DateTimeImmutable('11:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->startsBefore($timestamp));
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanCheckWhetherSpanEndsAfterTimestamp($span)
    {
        $datetime = new DateTimeImmutable('07:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->endsAfter($timestamp));
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanCheckWhetherSpanEndsBeforeTimestamp($span)
    {
        $datetime = new DateTimeImmutable('13:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->endsBefore($timestamp));
    }

    /**
     * @dataProvider provideDateTime
     */
    public function testCanBeSplittedByTimestamp($start, $end)
    {
        $half = ($start->getTimestamp() + $end->getTimestamp()) / 2;

        $span = Span::fromDateTime($start, $end);
        $parts = $span->splitTimestamp($half);

        $this->assertCount(2, $parts);
        $this->assertEquals($start->getTimestamp(), $parts[0]->getStart());
        $this->assertEquals($end->getTimestamp(), $parts[1]->getEnd());
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanBeSplittedToEqualParts($span)
    {
        foreach ([2, 4, 6, 8, 10] as $count) {
            $parts = $span->splitParts($count);
            $this->assertCount($count, $parts);
            $this->assertEquals($span->getStart(), $parts[0]->getStart());
            $this->assertEquals($span->getEnd(), $parts[$count - 1]->getEnd());
        }
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanBeSplittedAndMergedBack($span)
    {
        foreach ([2, 4, 6, 8, 10] as $count) {
            $parts = $span->splitParts($count);

            /** @var Span $firstPart */
            $firstPart = array_shift($parts);

            $result = $firstPart->merge(...$parts);

            $this->assertSame($span->getStart(), $result->getStart());
            $this->assertSame($span->getEnd(), $result->getEnd());
        }
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanCheckWhetherSpanContainsParticularTimestamp($span)
    {
        $half = ($span->getStart() + $span->getEnd()) / 2;

        $this->assertTrue($span->contains($span->getStart()));
        $this->assertTrue($span->contains($span->getEnd()));
        $this->assertTrue($span->contains($half));
    }

    /**
     * @dataProvider provideSpan
     */
    public function testThrowsExceptionWhenTimestampIsNotInSpanWhenSplittingByTimestamp($span)
    {
        $this->expectException(InvalidArgumentException::class);

        $span->splitTimestamp($span->getStart() - 100);
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanGetMiddleTimestamp($span)
    {
        $middle = new DateTimeImmutable('10:00');

        $this->assertSame($middle->getTimestamp(), $span->getMiddle());

    }

    public function testCanBeMerged()
    {
        $a = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

        $b = Span::fromDateTime(
            new DateTimeImmutable('10:00'),
            new DateTimeImmutable('13:00')
        );

        $c = $a->merge($b);

        $this->assertSame($a->getStart(), $c->getStart());
        $this->assertSame($b->getEnd(), $c->getEnd());
    }

    public function testCanGetFractionDuration()
    {
        $this->assertSame(10.0, Span::make(0, 20)->getFractionDuration(2));
        $this->assertSame(2.5, Span::make(10, 20)->getFractionDuration(4));
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanBeConvertedToPrimitives($span)
    {
        list($start, $end) = $span->toPrimitives();

        $this->assertSame($span->getStart(), $start);
        $this->assertSame($span->getEnd(), $end);
    }

    /**
     * @dataProvider provideSpan
     */
    public function testCanAddOffset($span)
    {
        $offset = 3600;
        $result = $span->offset($offset);

        $this->assertNotSame($span, $result);
        $this->assertSame($span->getStart() + $offset, $result->getStart());
        $this->assertSame($span->getEnd() + $offset, $result->getEnd());
    }

}