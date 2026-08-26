<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Geo;

use InvalidArgumentException;

final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('Invalid latitude.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('Invalid longitude.');
        }
    }
}
