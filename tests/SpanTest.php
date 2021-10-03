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

        $this->assertSame(7200, $span->duration());
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

    /**
     * @depends testCanBeCreatedFromDatetime
     */
    public function testCanIterateGaps()
    {
        /** @var Span[] $spans */
        $spans = [
            Span::fromDateTime(
                new DateTimeImmutable('09:00'),
                new DateTimeImmutable('11:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('11:00'),
                new DateTimeImmutable('12:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('13:00'),
                new DateTimeImmutable('15:00')
            ),
        ];

        $generator = $spans[0]->iterateGaps($spans[1], $spans[2]);

        $this->assertInstanceOf(Generator::class, $generator);
        $this->assertIsInt($generator->current());
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

    public function testCanFindSpansBetween()
    {
        $a = Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        );

        $b = Span::fromDateTime(
            new DateTimeImmutable('16:00'),
            new DateTimeImmutable('17:00')
        );

        $spans = [
            Span::fromDateTime(
                new DateTimeImmutable('11:00'),
                new DateTimeImmutable('12:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('12:00'),
                new DateTimeImmutable('13:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('13:00'),
                new DateTimeImmutable('14:00')
            ),
        ];

        $found = Span::findBetween($a, $b, $spans);

        $this->assertIsArray($found);
        $this->assertCount(3, $found);
        $this->assertSame($spans[0], $found[0]);
        $this->assertSame($spans[2], $found[2]);
    }

    public function testCanSumDurations()
    {
        $spans = [
            Span::fromDateTime(
                new DateTimeImmutable('08:00'),
                new DateTimeImmutable('12:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('13:00'),
                new DateTimeImmutable('17:00')
            ),
        ];

        $sum = Span::sumDurations(...$spans);

        $this->assertIsInt($sum);
        $this->assertSame(28800, $sum);
    }

    public function testCanSumGapsBetween()
    {
        $spans = [
            Span::fromDateTime(
                new DateTimeImmutable('09:00'),
                new DateTimeImmutable('10:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('11:00'),
                new DateTimeImmutable('12:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('14:00'),
                new DateTimeImmutable('15:00')
            ),
        ];

        $sum = Span::sumGaps(...$spans);

        $this->assertIsInt($sum);
        $this->assertSame(10800, $sum);
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

}