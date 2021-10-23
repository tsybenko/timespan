<?php

namespace Tsybenko\TimeSpan;

interface SpanInterface
{
    public function getStart(): int;

    public function getEnd(): int;

    public function getDuration(): int;

    public function gap(self $span): int;

    public function hasGap(self $span): bool;

    public function overlaps(self $span): bool;

    public function startsAfter(int $timestamp): bool;

    public function startsBefore(int $timestamp): bool;

    public function endsAfter(int $timestamp): bool;

    public function endsBefore(int $timestamp): bool;
}