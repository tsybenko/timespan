<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tsybenko\TimeSpan\Span;

class SpanTest extends TestCase
{
    public function testThrowsExceptionWhenEndLessThanStart()
    {
        $this->expectException(InvalidArgumentException::class);

        $start = 100;
        $end = 50;

        new Span($start, $end);
    }

    public function testCanBeCreatedFromTimestamp()
    {
        $start = new DateTimeImmutable('09:00');
        $end = new DateTimeImmutable('11:00');

        $this->assertInstanceOf(
            Span::class,
            new Span(
                $start->getTimestamp(),
                $end->getTimestamp()
            )
        );
    }

    public function testCanBeCreatedFromStaticConstructor()
    {
        $start = new DateTimeImmutable('09:00');
        $end = new DateTimeImmutable('11:00');

        $this->assertInstanceOf(
            Span::class,
            Span::make(
                $start->getTimestamp(),
                $end->getTimestamp()
            )
        );
    }

    public function testCanBeCreatedFromDatetime()
    {
        $this->assertInstanceOf(
            Span::class,
            Span::fromDateTime(
                new DateTimeImmutable('09:00'),
                new DateTimeImmutable('11:00')
            )
        );
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     */
    public function testCanCalculateDuration()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

        $this->assertSame(7200, $span->getDuration());
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     */
    public function testCanBeConvertedToDatePeriod()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

        $interval = new DateInterval('PT1H');

        $this->assertInstanceOf(DatePeriod::class, $span->toDatePeriod($interval));
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     */
    public function testCanBeConvertedToArray()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

        $arr = $span->toArray();

        $this->assertIsArray($span->toArray());
        $this->assertArrayHasKey('start', $arr);
        $this->assertArrayHasKey('end', $arr);
    }

    /**
     * @depends testCanBeCreatedFromDatetime
     */
    public function testCanBeConvertedToJson()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

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

    public function testCanCheckWhetherSpanStartsAfterTimestamp()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        $datetime = new DateTimeImmutable('08:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->startsAfter($timestamp));
    }

    public function testCanCheckWhetherSpanStartsBeforeTimestamp()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        $datetime = new DateTimeImmutable('11:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->startsBefore($timestamp));
    }

    public function testCanCheckWhetherSpanEndsAfterTimestamp()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        $datetime = new DateTimeImmutable('07:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->endsAfter($timestamp));
    }

    public function testCanCheckWhetherSpanEndsBeforeTimestamp()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        $datetime = new DateTimeImmutable('13:00');
        $timestamp = $datetime->getTimestamp();

        $this->assertTrue($span->endsBefore($timestamp));
    }

    public function testCanBeSplittedByTimestamp()
    {
        $start = new DateTimeImmutable('09:00');
        $end = new DateTimeImmutable('10:00');
        $half = ($start->getTimestamp() + $end->getTimestamp()) / 2;

        $span = Span::fromDateTime($start, $end);
        $parts = $span->splitTimestamp($half);

        $this->assertCount(2, $parts);
        $this->assertEquals($start->getTimestamp(), $parts[0]->getStart());
        $this->assertEquals($end->getTimestamp(), $parts[1]->getEnd());
    }

    public function testCanBeSplittedToEqualParts()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        foreach ([2, 3, 5, 7, 10] as $count) {
            $parts = $span->splitParts($count);
            $this->assertCount($count, $parts);
            $this->assertEquals($span->getStart(), $parts[0]->getStart());
            $this->assertEquals($span->getEnd(), $parts[$count - 1]->getEnd());
        }
    }

    public function testCanCheckWhetherSpanContainsParticularTimestamp()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        $half = ($span->getStart() + $span->getEnd()) / 2;

        $this->assertTrue($span->contains($span->getStart()));
        $this->assertTrue($span->contains($span->getEnd()));
        $this->assertTrue($span->contains($half));
    }

    public function testThrowsExceptionWhenTimestampIsNotInSpanWhenSplittingByTimestamp() {
        $this->expectException(InvalidArgumentException::class);

        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        $span->splitTimestamp($span->getStart() - 100);
    }

    public function testCanGetMiddleTimestamp()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

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

    public function testCanBeConvertedToPrimitives()
    {
        $span = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('11:00')
        );

        list($start, $end) = $span->toPrimitives();

        $this->assertSame($span->getStart(), $start);
        $this->assertSame($span->getEnd(), $end);
    }

}