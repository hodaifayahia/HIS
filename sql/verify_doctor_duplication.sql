-- Doctor Duplication Verification Script
-- Run this script to verify doctor duplication results

-- Set your source and target doctor IDs
SET @source_doctor_id = 5;   -- Change this to your source doctor ID
SET @target_doctor_id = 42;  -- Change this to your target doctor ID

-- ============================================================================
-- COMPLETE VERIFICATION
-- ============================================================================

SELECT '=== DOCTOR DUPLICATION VERIFICATION ===' as '';

-- Doctor Information
SELECT 'DOCTOR INFORMATION' as '';
SELECT 
    d.id as doctor_id,
    u.name as doctor_name,
    u.email,
    d.specialization_id,
    d.time_slot,
    d.frequency,
    d.is_active
FROM doctors d
JOIN users u ON d.user_id = u.id
WHERE d.id IN (@source_doctor_id, @target_doctor_id)
ORDER BY d.id;

-- Count Comparison
SELECT 'CONFIGURATION COUNTS' as '';
SELECT 
    'Schedules' as entity,
    (SELECT COUNT(*) FROM schedules WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM schedules WHERE doctor_id = @target_doctor_id) as target_count,
    CASE 
        WHEN (SELECT COUNT(*) FROM schedules WHERE doctor_id = @source_doctor_id) = 
             (SELECT COUNT(*) FROM schedules WHERE doctor_id = @target_doctor_id) 
        THEN '✓ MATCH' 
        ELSE '✗ MISMATCH' 
    END as status
UNION ALL
SELECT 
    'Appointment Months',
    (SELECT COUNT(*) FROM appointment_available_month WHERE doctor_id = @source_doctor_id),
    (SELECT COUNT(*) FROM appointment_available_month WHERE doctor_id = @target_doctor_id),
    CASE 
        WHEN (SELECT COUNT(*) FROM appointment_available_month WHERE doctor_id = @source_doctor_id) = 
             (SELECT COUNT(*) FROM appointment_available_month WHERE doctor_id = @target_doctor_id) 
        THEN '✓ MATCH' 
        ELSE '✗ MISMATCH' 
    END
UNION ALL
SELECT 
    'Folders',
    (SELECT COUNT(*) FROM folders WHERE doctor_id = @source_doctor_id),
    (SELECT COUNT(*) FROM folders WHERE doctor_id = @target_doctor_id),
    CASE 
        WHEN (SELECT COUNT(*) FROM folders WHERE doctor_id = @source_doctor_id) = 
             (SELECT COUNT(*) FROM folders WHERE doctor_id = @target_doctor_id) 
        THEN '✓ MATCH' 
        ELSE '✗ MISMATCH' 
    END
UNION ALL
SELECT 
    'Placeholders',
    (SELECT COUNT(*) FROM placeholders WHERE doctor_id = @source_doctor_id),
    (SELECT COUNT(*) FROM placeholders WHERE doctor_id = @target_doctor_id),
    CASE 
        WHEN (SELECT COUNT(*) FROM placeholders WHERE doctor_id = @source_doctor_id) = 
             (SELECT COUNT(*) FROM placeholders WHERE doctor_id = @target_doctor_id) 
        THEN '✓ MATCH' 
        ELSE '✗ MISMATCH' 
    END
UNION ALL
SELECT 
    'Attributes',
    (SELECT COUNT(*) FROM attributes WHERE doctor_id = @source_doctor_id),
    (SELECT COUNT(*) FROM attributes WHERE doctor_id = @target_doctor_id),
    CASE 
        WHEN (SELECT COUNT(*) FROM attributes WHERE doctor_id = @source_doctor_id) = 
             (SELECT COUNT(*) FROM attributes WHERE doctor_id = @target_doctor_id) 
        THEN '✓ MATCH' 
        ELSE '✗ MISMATCH' 
    END
UNION ALL
SELECT 
    'Templates',
    (SELECT COUNT(*) FROM templates WHERE doctor_id = @source_doctor_id),
    (SELECT COUNT(*) FROM templates WHERE doctor_id = @target_doctor_id),
    CASE 
        WHEN (SELECT COUNT(*) FROM templates WHERE doctor_id = @source_doctor_id) = 
             (SELECT COUNT(*) FROM templates WHERE doctor_id = @target_doctor_id) 
        THEN '✓ MATCH' 
        ELSE '✗ MISMATCH' 
    END
UNION ALL
SELECT 
    'Medication Favorites',
    (SELECT COUNT(*) FROM medication_doctor_favorats WHERE doctor_id = @source_doctor_id),
    (SELECT COUNT(*) FROM medication_doctor_favorats WHERE doctor_id = @target_doctor_id),
    CASE 
        WHEN (SELECT COUNT(*) FROM medication_doctor_favorats WHERE doctor_id = @source_doctor_id) = 
             (SELECT COUNT(*) FROM medication_doctor_favorats WHERE doctor_id = @target_doctor_id) 
        THEN '✓ MATCH' 
        ELSE '✗ MISMATCH' 
    END;

-- Verify Folder Mapping
SELECT 'FOLDER VERIFICATION' as '';
SELECT 
    'Source' as type,
    id,
    name,
    doctor_id
FROM folders
WHERE doctor_id = @source_doctor_id
UNION ALL
SELECT 
    'Target',
    id,
    name,
    doctor_id
FROM folders
WHERE doctor_id = @target_doctor_id
ORDER BY name, type;

-- Verify Placeholder Mapping
SELECT 'PLACEHOLDER VERIFICATION' as '';
SELECT 
    'Source' as type,
    id,
    name,
    doctor_id,
    (SELECT COUNT(*) FROM attributes WHERE placeholder_id = p.id) as attribute_count
FROM placeholders p
WHERE doctor_id = @source_doctor_id
UNION ALL
SELECT 
    'Target',
    id,
    name,
    doctor_id,
    (SELECT COUNT(*) FROM attributes WHERE placeholder_id = p.id)
FROM placeholders p
WHERE doctor_id = @target_doctor_id
ORDER BY name, type;

-- Verify Attributes have correct doctor_id
SELECT 'ATTRIBUTE DOCTOR_ID VERIFICATION' as '';
SELECT 
    a.id,
    a.name,
    a.doctor_id as attribute_doctor_id,
    p.doctor_id as placeholder_doctor_id,
    CASE 
        WHEN a.doctor_id = p.doctor_id THEN '✓ OK' 
        ELSE '✗ MISMATCH' 
    END as status
FROM attributes a
JOIN placeholders p ON a.placeholder_id = p.id
WHERE p.doctor_id = @target_doctor_id
LIMIT 10;

-- Verify Templates
SELECT 'TEMPLATE VERIFICATION' as '';
SELECT 
    'Source' as type,
    t.id,
    t.name,
    t.doctor_id,
    t.folder_id,
    f.name as folder_name
FROM templates t
LEFT JOIN folders f ON t.folder_id = f.id
WHERE t.doctor_id = @source_doctor_id
UNION ALL
SELECT 
    'Target',
    t.id,
    t.name,
    t.doctor_id,
    t.folder_id,
    f.name
FROM templates t
LEFT JOIN folders f ON t.folder_id = f.id
WHERE t.doctor_id = @target_doctor_id
ORDER BY name, type;

-- Verify Template-Placeholder Associations
SELECT 'TEMPLATE-PLACEHOLDER ASSOCIATIONS' as '';
SELECT 
    'Target Doctor' as info,
    t.name as template_name,
    p.name as placeholder_name,
    pt.attribute_id,
    a.name as attribute_name,
    CASE 
        WHEN a.doctor_id = @target_doctor_id THEN '✓ OK' 
        ELSE '✗ WRONG DOCTOR' 
    END as attribute_doctor_check
FROM placeholder_templates pt
JOIN templates t ON pt.template_id = t.id
JOIN placeholders p ON pt.placeholder_id = p.id
LEFT JOIN attributes a ON pt.attribute_id = a.id
WHERE t.doctor_id = @target_doctor_id
LIMIT 20;

-- Summary
SELECT 'SUMMARY' as '';
SELECT 
    CASE 
        WHEN (
            SELECT COUNT(*) 
            FROM folders 
            WHERE doctor_id = @source_doctor_id
        ) = (
            SELECT COUNT(*) 
            FROM folders 
            WHERE doctor_id = @target_doctor_id
        ) AND (
            SELECT COUNT(*) 
            FROM placeholders 
            WHERE doctor_id = @source_doctor_id
        ) = (
            SELECT COUNT(*) 
            FROM placeholders 
            WHERE doctor_id = @target_doctor_id
        ) AND (
            SELECT COUNT(*) 
            FROM templates 
            WHERE doctor_id = @source_doctor_id
        ) = (
            SELECT COUNT(*) 
            FROM templates 
            WHERE doctor_id = @target_doctor_id
        )
        THEN '✓✓✓ ALL CONFIGURATIONS DUPLICATED SUCCESSFULLY ✓✓✓'
        ELSE '✗✗✗ SOME CONFIGURATIONS MISSING ✗✗✗'
    END as result;
