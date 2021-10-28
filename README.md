# Timespan

## Example
### Span
The one primitive type Span operates with, is an integer. 
The integer value commonly represents a timestamp.
```php
require_once __DIR__ . '/vendor/autoload.php';

use Tsybenko\TimeSpan\Span;

$start = new DateTimeImmutable('10:00');
$end = new DateTimeImmutable('12:30');

/** Can be initialized via default constructor */
$span = new Span(
    $start->getTimestamp(),
    $end->getTimestamp()
);

/** Can be initialized via static method */
$span = Span::make(
    $start->getTimestamp(),
    $end->getTimestamp()
);

/** Can be initialized using DateTimeInterface objects */
$span = Span::fromDateTime($start, $end);

print_r($span->toArray() + [
    'duration' => $span->getDuration(),
    'middle' => $span->getMiddle(),
    'json' => $span->toJson(),
    'string' => $span->toString(),
]);

/** Create three spans from a span */
list($part1, $part2, $part3) = $span->splitParts(3);

/** Merge parts back into a single span */
$span = $part1->merge($part2)->merge($part3);
// $span = $part1->merge($part2, $part3);

/** Get size of a gap between passed parts (spans) */
$gap = $part1->gap($part3);

print_r($gap === $part2->getDuration()); // true
print_r($part1->hasGap($part3)); // true

/** Creates date period with 30 minutes interval from the span */
$dateInterval = new DateInterval('PT30M'); // 30 minutes interval
$datePeriod = $span->toDatePeriod($dateInterval);

/** Split span into two parts by timestamp */
try {
    $timestamp = $part2->getStart();
    $parts = $span->splitTimestamp($timestamp);
    print_r($parts);
} catch (InvalidArgumentException $exception) {
    echo $exception->getMessage();
}

```