<?php

namespace App\Tests\Entity;

use App\Entity\BlockoutPeriod;
use PHPUnit\Framework\TestCase;

final class BlockoutPeriodTest extends TestCase
{
    public function testDatesAreNormalizedToFullHours(): void
    {
        $period = new BlockoutPeriod();

        $period->setStartDate(new \DateTimeImmutable('2026-09-05 09:37:42', new \DateTimeZone('Asia/Tokyo')));
        $period->setEndDate(new \DateTimeImmutable('2026-09-05 18:59:59', new \DateTimeZone('Asia/Tokyo')));

        self::assertSame('2026-09-05 09:00:00', $period->getStartDate()?->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-05 18:00:00', $period->getEndDate()?->format('Y-m-d H:i:s'));
    }
}
