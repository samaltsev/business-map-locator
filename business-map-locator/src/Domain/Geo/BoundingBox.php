<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Geo;

use InvalidArgumentException;

final readonly class BoundingBox
{
    public function __construct(
        public float $west,
        public float $south,
        public float $east,
        public float $north
    ) {
        if ($south < -90 || $north > 90 || $south > $north) {
            throw new InvalidArgumentException('Invalid latitude bounds.');
        }

        if ($west < -180 || $east > 180 || $west > $east) {
            throw new InvalidArgumentException('Invalid longitude bounds.');
        }
    }
}
