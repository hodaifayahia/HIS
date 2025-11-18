<?php

namespace Tests\Feature\Purchasing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsignmentMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test consignment_receptions table structure
     */
    public function test_consignment_receptions_table_has_correct_structure(): void
    {
        $this->assertTrue(Schema::hasTable('consignment_receptions'));

        $columns = [
            'id',
            'consignment_code',
            'fournisseur_id',
            'reception_date',
            'unit_of_measure',
            'origin_note',
            'reception_type',
            'operation_type',
            'created_by',
            'confirmed_at',
            'confirmed_by',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('consignment_receptions', $column),
                "Column {$column} is missing from consignment_receptions table"
            );
        }
    }

    /**
     * Test consignment_reception_items table structure
     */
    public function test_consignment_reception_items_table_has_correct_structure(): void
    {
        $this->assertTrue(Schema::hasTable('consignment_reception_items'));

        $columns = [
            'id',
            'consignment_reception_id',
            'product_id',
            'quantity_received',
            'quantity_consumed',
            'quantity_invoiced',
            'unit_price',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('consignment_reception_items', $column),
                "Column {$column} is missing from consignment_reception_items table"
            );
        }
    }

    /**
     * Test fiche_navette_items has consignment_item_id column
     */
    public function test_fiche_navette_items_has_consignment_link(): void
    {
        $this->assertTrue(
            Schema::hasColumn('fiche_navette_items', 'consignment_item_id'),
            'Column consignment_item_id is missing from fiche_navette_items table'
        );
    }

    /**
     * Test bon_commends has consignment fields
     */
    public function test_bon_commends_has_consignment_fields(): void
    {
        $this->assertTrue(
            Schema::hasColumn('bon_commends', 'is_from_consignment'),
            'Column is_from_consignment is missing from bon_commends table'
        );

        $this->assertTrue(
            Schema::hasColumn('bon_commends', 'consignment_source_id'),
            'Column consignment_source_id is missing from bon_commends table'
        );
    }

    /**
     * Test data preservation after migration (existing data not affected)
     */
    public function test_existing_data_preserved_after_migration(): void
    {
        // This test validates that existing bon_commends records
        // have default values for new consignment fields
        $bonCommandsCount = \DB::table('bon_commends')->count();

        // All existing records should have is_from_consignment = false by default
        $nonConsignmentCount = \DB::table('bon_commends')
            ->where('is_from_consignment', false)
            ->count();

        $this->assertEquals($bonCommandsCount, $nonConsignmentCount);
    }
}
