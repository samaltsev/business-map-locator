<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Geo;

enum DistanceUnit: string
{
    case Kilometres = 'km';
    case Miles = 'mi';

    public function earthRadius(): float
    {
        return $this === self::Miles ? 3958.7613 : 6371.0088;
    }
}
