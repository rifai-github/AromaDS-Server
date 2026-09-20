<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA bug: contract SMG-AG/26-10/0001 room "Musholla" showed Qty 0 / Qty Free 0,
 * while Catalyst MKTContractDt held Qty = 0, QtyFree = 1 - a unit that is
 * installed but not billed. The importer read Qty and PriceForex but never
 * QtyFree, so every imported contract/quotation rental landed with qty_free 0.
 */
class CatalystQtyFreeImportTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('qtyFreeRows')]
    public function test_source_qty_free_reads_the_column_whatever_its_casing(array $row, int $expected): void
    {
        $method = new ReflectionMethod(CatalystMasterDataImporter::class, 'sourceQtyFree');
        $method->setAccessible(true);

        $importer = (new ReflectionClass(CatalystMasterDataImporter::class))->newInstanceWithoutConstructor();

        $this->assertSame($expected, $method->invoke($importer, $row));
    }

    public static function qtyFreeRows(): array
    {
        return [
            'exact spelling' => [['Qty' => 0, 'QtyFree' => 1], 1],
            'upper case' => [['QTYFREE' => 2], 2],
            'lower case' => [['qtyfree' => 3], 3],
            'underscored' => [['Qty_Free' => 4], 4],
            'string value' => [['QtyFree' => '5'], 5],
            'null value' => [['QtyFree' => null], 0],
            'negative clamped' => [['QtyFree' => -1], 0],
            'column absent' => [['Qty' => 1, 'PriceForex' => 3600000], 0],
            'not a free-qty column' => [['QtyInstall' => 7], 0],
        ];
    }

    /**
     * The three steps that write a rental row must all carry the mapping; a
     * missing one fails silently as a 0 in the UI, which is how this shipped.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rentalSteps')]
    public function test_rental_import_steps_map_qty_free(string $step): void
    {
        $method = new ReflectionMethod(CatalystMasterDataImporter::class, $step);
        $lines = file($method->getFileName());
        $body = implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        $this->assertStringContainsString('$this->sourceQtyFree($row)', $body, "{$step}() never reads QtyFree from the source row.");
        $this->assertStringContainsString("'qty_free' => \$qtyFree", $body, "{$step}() never writes qty_free to the target row.");
    }

    public static function rentalSteps(): array
    {
        return [
            ['contract_rentals'],
            ['quotation_rentals'],
            ['quotation_details'],
        ];
    }
}
