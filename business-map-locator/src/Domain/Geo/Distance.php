<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Geo;

use InvalidArgumentException;

final readonly class Distance
{
    public function __construct(
        public float $value,
        public DistanceUnit $unit
    ) {
        if ($value < 1 || $value > 500) {
            throw new InvalidArgumentException('Invalid radius.');
        }
    }
}
