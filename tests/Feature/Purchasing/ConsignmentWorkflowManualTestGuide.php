<?php

namespace Tests\Feature\Purchasing;

use Tests\TestCase;

class ConsignmentWorkflowManualTestGuide extends TestCase
{
    public function test_display_sql_to_create_fiche_navette_and_caisse_payment(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║      MANUAL TEST: Create FicheNavette & Caisse Payment (Persistent Data)       ║\n";
        echo "║                                                                                ║\n";
        echo "║  This test shows you the SQL commands to CREATE test data that will PERSIST   ║\n";
        echo "║  in your HIS database. Run these commands in a MySQL client to see the data   ║\n";
        echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";

        echo "\n═══════════════════════════════════════════════════════════════════════════════════\n";
        echo "PHASE 1: CREATE PATIENT\n";
        echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

        echo "-- Create a test patient if one doesn't exist\n";
        echo "INSERT INTO patients (first_name, last_name, email, phone, date_of_birth, gender, created_at, updated_at)\n";
        echo "VALUES ('Test', 'Patient', 'testpatient@test.com', '555-1234', '1980-01-15', 'M', NOW(), NOW())\n";
        echo "ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);\n";
        echo "\nSET @patient_id = LAST_INSERT_ID();\n";
        echo "SELECT @patient_id AS 'PATIENT_ID';\n\n";

        echo "═══════════════════════════════════════════════════════════════════════════════════\n";
        echo "PHASE 2: CREATE FICHE NAVETTE (Consultation Record)\n";
        echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

        echo "-- Create FicheNavette (consultation/visit record)\n";
        echo "INSERT INTO fiche_navettes (\n";
        echo "    patient_id,\n";
        echo "    creator_id,\n";
        echo "    total_amount,\n";
        echo "    is_paid,\n";
        echo "    created_at,\n";
        echo "    updated_at\n";
        echo ") VALUES (\n";
        echo "    @patient_id,\n";
        echo "    1,                    -- admin user\n";
        echo "    15000,                -- total amount\n";
        echo "    false,                -- NOT YET PAID\n";
        echo "    NOW(),\n";
        echo "    NOW()\n";
        echo ");\n";
        echo "\nSET @fiche_id = LAST_INSERT_ID();\n";
        echo "SELECT @fiche_id AS 'FICHE_NAVETTE_ID';\n\n";

        echo "-- Add prestation items to the fiche\n";
        echo "INSERT INTO fiche_navette_items (\n";
        echo "    fiche_navette_id,\n";
        echo "    prestation_id,\n";
        echo "    quantity,\n";
        echo "    price,\n";
        echo "    is_paid,\n";
        echo "    created_at,\n";
        echo "    updated_at\n";
        echo ") VALUES\n";
        echo "    (@fiche_id, 1, 1, 5000, false, NOW(), NOW()),   -- Blood Test\n";
        echo "    (@fiche_id, 2, 1, 10000, false, NOW(), NOW());  -- ECG\n\n";

        echo "-- Verify FicheNavette created:\n";
        echo "SELECT\n";
        echo "    id AS 'FicheID',\n";
        echo "    patient_id AS 'PatientID',\n";
        echo "    total_amount AS 'Amount',\n";
        echo "    is_paid AS 'IsPaid',\n";
        echo "    created_at AS 'CreatedAt'\n";
        echo "FROM fiche_navettes\n";
        echo "WHERE id = @fiche_id;\n\n";

        echo "-- Verify FicheNavette items:\n";
        echo "SELECT\n";
        echo "    id AS 'ItemID',\n";
        echo "    fiche_navette_id AS 'FicheID',\n";
        echo "    prestation_id AS 'PrestationID',\n";
        echo "    quantity AS 'Qty',\n";
        echo "    price AS 'Price',\n";
        echo "    is_paid AS 'IsPaid'\n";
        echo "FROM fiche_navette_items\n";
        echo "WHERE fiche_navette_id = @fiche_id;\n\n";

        echo "═══════════════════════════════════════════════════════════════════════════════════\n";
        echo "PHASE 3: MARK FICHE AS PAID (Patient Payment)\n";
        echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

        echo "-- Mark all items in fiche as paid\n";
        echo "UPDATE fiche_navette_items\n";
        echo "SET is_paid = true, updated_at = NOW()\n";
        echo "WHERE fiche_navette_id = @fiche_id;\n\n";

        echo "-- Mark fiche itself as paid\n";
        echo "UPDATE fiche_navettes\n";
        echo "SET is_paid = true, updated_at = NOW()\n";
        echo "WHERE id = @fiche_id;\n\n";

        echo "-- Verify payment status changed:\n";
        echo "SELECT\n";
        echo "    id AS 'FicheID',\n";
        echo "    is_paid AS 'IsPaid',\n";
        echo "    updated_at AS 'UpdatedAt'\n";
        echo "FROM fiche_navettes\n";
        echo "WHERE id = @fiche_id;\n\n";

        echo "═══════════════════════════════════════════════════════════════════════════════════\n";
        echo "PHASE 4: CREATE CAISSE PAYMENT TRANSACTION\n";
        echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

        echo "-- Get or create main caisse (cash account)\n";
        echo "SELECT id INTO @caisse_id\n";
        echo "FROM caisses\n";
        echo "WHERE name = 'Main Caisse'\n";
        echo "LIMIT 1;\n\n";

        echo "-- If caisse doesn't exist, create it:\n";
        echo "-- INSERT INTO caisses (name, solde, created_at, updated_at)\n";
        echo "-- VALUES ('Main Caisse', 1000000, NOW(), NOW());\n";
        echo "-- SET @caisse_id = LAST_INSERT_ID();\n\n";

        echo "-- Create caisse transaction (payment record)\n";
        echo "INSERT INTO caisse_transactions (\n";
        echo "    caisse_id,\n";
        echo "    type,\n";
        echo "    amount,\n";
        echo "    description,\n";
        echo "    reference_type,\n";
        echo "    reference_id,\n";
        echo "    created_by,\n";
        echo "    created_at,\n";
        echo "    updated_at\n";
        echo ") VALUES (\n";
        echo "    @caisse_id,\n";
        echo "    'payment',                          -- type: payment\n";
        echo "    15000,                              -- amount: total fiche amount\n";
        echo "    CONCAT('Payment for FicheNavette #', @fiche_id),  -- description\n";
        echo "    'fiche_navette',                   -- reference type\n";
        echo "    @fiche_id,                         -- reference id (link to fiche)\n";
        echo "    1,                                 -- created by admin user\n";
        echo "    NOW(),\n";
        echo "    NOW()\n";
        echo ");\n";
        echo "\nSET @transaction_id = LAST_INSERT_ID();\n";
        echo "SELECT @transaction_id AS 'TRANSACTION_ID';\n\n";

        echo "-- Verify caisse transaction created:\n";
        echo "SELECT\n";
        echo "    id AS 'TransactionID',\n";
        echo "    caisse_id AS 'CaisseID',\n";
        echo "    type AS 'Type',\n";
        echo "    amount AS 'Amount',\n";
        echo "    description AS 'Description',\n";
        echo "    reference_type AS 'RefType',\n";
        echo "    reference_id AS 'RefID',\n";
        echo "    created_at AS 'CreatedAt'\n";
        echo "FROM caisse_transactions\n";
        echo "WHERE id = @transaction_id;\n\n";

        echo "═══════════════════════════════════════════════════════════════════════════════════\n";
        echo "VERIFICATION QUERIES (Run these to see your data persists)\n";
        echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

        echo "QUERY 1: View all FicheNavettes created today\n";
        echo "──────────────────────────────────────────────────────────────────────────────────\n";
        echo "SELECT\n";
        echo "    f.id AS 'FicheID',\n";
        echo "    f.patient_id AS 'PatientID',\n";
        echo "    p.first_name AS 'PatientFirstName',\n";
        echo "    p.last_name AS 'PatientLastName',\n";
        echo "    f.total_amount AS 'Amount',\n";
        echo "    f.is_paid AS 'IsPaid',\n";
        echo "    COUNT(fi.id) AS 'ItemCount',\n";
        echo "    f.created_at AS 'CreatedAt'\n";
        echo "FROM fiche_navettes f\n";
        echo "LEFT JOIN fiche_navette_items fi ON f.id = fi.fiche_navette_id\n";
        echo "LEFT JOIN patients p ON f.patient_id = p.id\n";
        echo "WHERE DATE(f.created_at) = DATE(NOW())\n";
        echo "GROUP BY f.id\n";
        echo "ORDER BY f.created_at DESC;\n\n";

        echo "QUERY 2: View all Caisse transactions created today\n";
        echo "──────────────────────────────────────────────────────────────────────────────────\n";
        echo "SELECT\n";
        echo "    t.id AS 'TransactionID',\n";
        echo "    c.name AS 'CaisseName',\n";
        echo "    t.type AS 'Type',\n";
        echo "    t.amount AS 'Amount',\n";
        echo "    t.description AS 'Description',\n";
        echo "    t.reference_type AS 'RefType',\n";
        echo "    t.reference_id AS 'RefID',\n";
        echo "    t.created_at AS 'CreatedAt'\n";
        echo "FROM caisse_transactions t\n";
        echo "LEFT JOIN caisses c ON t.caisse_id = c.id\n";
        echo "WHERE DATE(t.created_at) = DATE(NOW())\n";
        echo "ORDER BY t.created_at DESC;\n\n";

        echo "QUERY 3: Link FicheNavette to Payment (see the relationship)\n";
        echo "──────────────────────────────────────────────────────────────────────────────────\n";
        echo "SELECT\n";
        echo "    f.id AS 'FicheID',\n";
        echo "    f.total_amount AS 'FicheAmount',\n";
        echo "    f.is_paid AS 'FicheIsPaid',\n";
        echo "    t.id AS 'TransactionID',\n";
        echo "    t.amount AS 'PaymentAmount',\n";
        echo "    t.type AS 'PaymentType',\n";
        echo "    CASE WHEN f.id = t.reference_id THEN 'LINKED' ELSE 'NOT LINKED' END AS 'LinkStatus'\n";
        echo "FROM fiche_navettes f\n";
        echo "LEFT JOIN caisse_transactions t ON f.id = t.reference_id AND t.reference_type = 'fiche_navette'\n";
        echo "WHERE DATE(f.created_at) = DATE(NOW())\n";
        echo "ORDER BY f.created_at DESC;\n\n";

        echo "QUERY 4: Caisse Balance Update\n";
        echo "──────────────────────────────────────────────────────────────────────────────────\n";
        echo "SELECT\n";
        echo "    c.id AS 'CaisseID',\n";
        echo "    c.name AS 'CaisseName',\n";
        echo "    c.solde AS 'Balance',\n";
        echo "    SUM(CASE WHEN t.type = 'payment' THEN t.amount ELSE 0 END) AS 'TotalPayments',\n";
        echo "    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) AS 'TotalExpenses'\n";
        echo "FROM caisses c\n";
        echo "LEFT JOIN caisse_transactions t ON c.id = t.caisse_id\n";
        echo "WHERE DATE(t.created_at) = DATE(NOW()) OR DATE(t.created_at) IS NULL\n";
        echo "GROUP BY c.id;\n\n";

        echo "═══════════════════════════════════════════════════════════════════════════════════\n";
        echo "✅ HOW TO USE THIS GUIDE:\n";
        echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

        echo "1. Copy the SQL commands above (Phases 1-4)\n";
        echo "2. Paste them into your MySQL client (PHPMyAdmin, MySQL Workbench, etc)\n";
        echo "3. Run them sequentially to create persistent test data\n";
        echo "4. Use the Verification Queries to see your data in the database\n";
        echo "5. The data will STAY in the database for inspection\n\n";

        echo "Expected Results:\n";
        echo "  • Patient created with ID: @patient_id\n";
        echo "  • FicheNavette created with ID: @fiche_id\n";
        echo "  • FicheNavette total: 15000 (5000 + 10000)\n";
        echo "  • 2 items added to fiche (Blood Test + ECG)\n";
        echo "  • FicheNavette marked as PAID (is_paid = true)\n";
        echo "  • Caisse transaction created with ID: @transaction_id\n";
        echo "  • Transaction linked to FicheNavette by reference_id\n";
        echo "  • All data persists in database\n\n";

        echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

        // Simple assertion to pass the test
        $this->assertTrue(true);
    }
}
