<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tsybenko\TimeSpan\Span;
use Tsybenko\TimeSpan\Timeline;

class TimelineTest extends TestCase
{
    public function testCanGetDuration()
    {
        $timeline = new Timeline();
        $span = new Span(100, 200);

        $timeline->add($span);

        $this->assertSame(100, $timeline->getDuration());
    }

    public function testCanAddSpans()
    {
        $timeline = new Timeline();

        for ($i = 0; $i < 3; $i++) {
            $span = $this->createMock(Span::class);
            $timeline->add($span);
        }

        $this->assertCount(3, $timeline->getSpans());
    }

    public function testCanMergeTimelines()
    {
        $timeline = new Timeline();

        $candidateA = new Timeline();
        $candidateB = new Timeline();

        $firstSpan = Span::fromDateTime(
            new DateTimeImmutable('06:00'),
            new DateTimeImmutable('07:00')
        );
        $lastSpan = Span::fromDateTime(
            new DateTimeImmutable('11:00'),
            new DateTimeImmutable('12:00')
        );

        $candidateA->add($firstSpan);
        $candidateA->add(Span::fromDateTime(
            new DateTimeImmutable('09:00'),
            new DateTimeImmutable('10:00')
        ));

        $candidateB->add(Span::fromDateTime(
            new DateTimeImmutable('07:00'),
            new DateTimeImmutable('08:00')
        ));
        $candidateB->add($lastSpan);

        $result = $timeline->merge($candidateA, $candidateB);
        $resultSpans = $result->getSpans();

        $this->assertCount(4, $result->getSpans());
        $this->assertSame($firstSpan, $resultSpans[array_key_first($resultSpans)]);
        $this->assertSame($lastSpan, $resultSpans[array_key_last($resultSpans)]);
    }

    public function testCanGenerateFromTimestamp()
    {
        $start = new DateTimeImmutable('09:00');

        $timeline = Timeline::generateFrom($start->getTimestamp(), 3600, 5, 1800);
        $spans = $timeline->getSpans();

        $this->assertCount(5, $spans);
        $this->assertSame($start->getTimestamp(), $spans[array_key_first($spans)]->getStart());
    }

    public function testCanGenerateToTimestamp()
    {
        $end = new DateTimeImmutable('09:00');

        $timeline = Timeline::generateTo($end->getTimestamp(), 3600, 5, 1800);
        $spans = $timeline->getSpans();

        $this->assertCount(5, $spans);
        $this->assertSame($end->getTimestamp(), $spans[array_key_last($spans)]->getEnd());
    }
}
