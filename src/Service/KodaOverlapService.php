<?php

namespace App\Service;

use App\Entity\Zone;

class KodaOverlapService
{
    public const array ZONE_OVERLAPS = [
        'ZONEA' => ['ZONE_AB', 'ZONEFULL'],
        'ZONEB' => ['ZONE_AB', 'ZONEFULL'],
        'ZONEC' => ['ZONE_CD', 'ZONEFULL'],
        'ZONED' => ['ZONE_CD', 'ZONEFULL'],
        'ZONE_AB' => ['ZONEA', 'ZONEB', 'ZONEFULL'],
        'ZONE_CD' => ['ZONEC', 'ZONED', 'ZONEFULL'],
        'ZONEFULL' => ['ZONEA', 'ZONEB', 'ZONEC', 'ZONED', 'ZONE_AB', 'ZONE_CD'],
    ];

    public function getConflictingZoneCodes(Zone $zone): array
    {
        $code = $zone->getCode();

        if (!$code) {
            return [];
        }

        return self::ZONE_OVERLAPS[$code] ?? [];
    }
}
