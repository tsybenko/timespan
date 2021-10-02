<?php

namespace Tsybenko\TimeSpan;

use DateTimeImmutable;

class Event extends Span
{
    public static function allDayLong($date)
    {
        $start = new DateTimeImmutable("$date midnight");
        $end = new DateTimeImmutable("$date + 1 day midnight");

        return static::fromDateTime($start, $end);
    }
}