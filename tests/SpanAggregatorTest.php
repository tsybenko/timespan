<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tsybenko\TimeSpan\Span;
use Tsybenko\TimeSpan\SpanAggregator;

class SpanAggregatorTest extends TestCase
{
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

        $aggregator = new SpanAggregator($spans[0]);
        $generator = $aggregator->iterateGaps($spans[1], $spans[2]);

        $this->assertInstanceOf(Generator::class, $generator);
        $this->assertIsInt($generator->current());
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

        $sum = SpanAggregator::sumDurations(...$spans);

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

        $sum = SpanAggregator::sumGaps(...$spans);

        $this->assertIsInt($sum);
        $this->assertSame(10800, $sum);
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

        $found = SpanAggregator::findBetween($a, $b, $spans);

        $this->assertIsArray($found);
        $this->assertCount(3, $found);
        $this->assertSame($spans[0], $found[0]);
        $this->assertSame($spans[2], $found[2]);
    }

    public function testCanFindMostDurable()
    {
        $spans = [
            Span::fromDateTime(
                new DateTimeImmutable('11:00'),
                new DateTimeImmutable('12:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('12:00'),
                new DateTimeImmutable('14:00')
            ),
            Span::fromDateTime(
                new DateTimeImmutable('14:00'),
                new DateTimeImmutable('17:00')
            ),
        ];

        $mostDurable = SpanAggregator::findMostDurable(...$spans);
        $this->assertSame($spans[2], $mostDurable);
    }

    public function testCanCheckIsTargetBetweenTwoSpans()
    {
        $a = Span::fromDateTime(
            new DateTimeImmutable('11:00'),
            new DateTimeImmutable('12:00')
        );

        $b = Span::fromDateTime(
            new DateTimeImmutable('14:00'),
            new DateTimeImmutable('17:00')
        );

        $target = Span::fromDateTime(
            new DateTimeImmutable('12:00'),
            new DateTimeImmutable('14:00')
        );

        $this->assertTrue(SpanAggregator::isBetween($a, $b, $target));
    }

}