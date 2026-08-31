<?php
declare(strict_types=1);

use BusinessMapLocator\Application\Location\SearchLocationsQuery;
use PHPUnit\Framework\TestCase;

final class SearchLocationsQueryViewportRadiusContractTest extends TestCase
{
    public function testQueryPreservesViewportBoundsAlongsideNearMeValues(): void
    {
        $query = SearchLocationsQuery::fromArray([
            'north' => '53.95', 'south' => '53.80', 'east' => '27.75', 'west' => '27.40',
            'lat' => '53.90', 'lng' => '27.56', 'radius' => '25', 'unit' => 'km',
        ]);

        self::assertNotNull($query->boundingBox);
        self::assertSame(53.95, $query->boundingBox->north);
        self::assertSame(53.80, $query->boundingBox->south);
        self::assertSame(27.75, $query->boundingBox->east);
        self::assertSame(27.40, $query->boundingBox->west);
        self::assertNotNull($query->origin);
        self::assertNotNull($query->radius);
    }

    public function testRepositoryAppliesViewportAndRadiusBoundingBoxesTogether(): void
    {
        $repository = (string) file_get_contents(dirname(__DIR__) . '/src/Infrastructure/Database/LocationRepository.php');

        self::assertStringContainsString('if ($query->boundingBox) {', $repository);
        self::assertStringContainsString('$this->appendBoundingBox($where, $values, $query->boundingBox);', $repository);
        self::assertStringContainsString('$this->appendBoundingBox($where, $values, $this->radiusBoundingBox($query->origin, $query->radius));', $repository);
        self::assertStringContainsString("' HAVING distance <= %f'", $repository);
        self::assertStringNotContainsString('$bbox = $this->radiusBoundingBox($query->origin, $query->radius);', $repository);
    }
}
