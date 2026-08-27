<?php
declare(strict_types=1);

use BusinessMapLocator\Import\Mapping\ImportMapper;
use PHPUnit\Framework\TestCase;

final class ImportOperationalStatusContractTest extends TestCase
{
    private ImportMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ImportMapper();
    }

    public function testOperationalStatusHeaderIsAccepted(): void
    {
        self::assertSame([], $this->mapper->validateHeaders(['title', 'lat', 'lng', 'operational_status']));
    }

    /** @dataProvider validOperationalStatuses */
    public function testMapsValidOperationalStatus(string $input, string $expected): void
    {
        $data = $this->mapper->map(['title', 'lat', 'lng', 'operational_status'], ['Store', '53.9', '27.56', $input]);

        self::assertTrue($this->mapper->validate($data)['valid']);
        self::assertSame($expected, $data['operational_status']);
    }

    /** @return array<string, array{string, string}> */
    public static function validOperationalStatuses(): array
    {
        return [
            'active' => ['active', 'active'],
            'temporarily closed' => ['temporarily_closed', 'temporarily_closed'],
            'hidden' => ['hidden', 'hidden'],
            'open alias' => ['open', 'active'],
        ];
    }

    public function testRejectsInvalidExplicitOperationalStatus(): void
    {
        $data = $this->mapper->map(['title', 'lat', 'lng', 'operational_status'], ['Store', '53.9', '27.56', 'banana']);

        self::assertFalse($this->mapper->validate($data)['valid']);
    }

    public function testEmptyOperationalStatusIsOptional(): void
    {
        $data = $this->mapper->map(['title', 'lat', 'lng', 'operational_status'], ['Store', '53.9', '27.56', '']);

        self::assertTrue($this->mapper->validate($data)['valid']);
        self::assertSame('', $data['operational_status']);
    }
}
