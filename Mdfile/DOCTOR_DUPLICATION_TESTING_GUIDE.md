# Doctor Duplication Testing Guide

## Quick Test Checklist

### ✅ Pre-Test Setup

1. **Identify Source Doctor**
   - Find a doctor with complete configuration
   - Note their ID from the database or UI

2. **Verify Source Doctor Has:**
   - ✅ Weekly schedules
   - ✅ Appointment available months
   - ✅ Custom date schedules (optional)
   - ✅ Excluded dates (optional)
   - ✅ Template folders
   - ✅ Consultation placeholders/sections with attributes
   - ✅ Consultation templates
   - ✅ Medication favorites

### 🧪 Test 1: Full Doctor Duplication

**API Call:**
```bash
curl -X POST http://localhost/api/doctors/5/duplicate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Dr. Test Duplicate",
    "email": "test.duplicate@hospital.com",
    "password": "TestPass123",
    "phone": "+1234567890"
  }'
```

**Expected Response (201):**
```json
{
  "message": "Doctor duplicated successfully",
  "data": {
    "id": 42,
    "user": {
      "name": "Dr. Test Duplicate",
      "email": "test.duplicate@hospital.com",
      "role": "doctor"
    },
    "specialization": { "id": 5, "name": "Cardiology" },
    "schedules": [...],
    "appointmentAvailableMonths": [...],
    "appointmentForce": [...],
    "excludedDates": [...]
  }
}
```

### 🔍 Verification Queries

#### 1. Verify New Doctor Created
```sql
SELECT d.id, u.name, u.email, u.role, d.specialization_id, d.time_slot
FROM doctors d
JOIN users u ON d.user_id = u.id
WHERE u.email = 'test.duplicate@hospital.com';
```

#### 2. Verify Folders Duplicated
```sql
-- Source folders
SELECT COUNT(*) as source_count 
FROM folders 
WHERE doctor_id = 5; -- Source doctor ID

-- Target folders
SELECT COUNT(*) as target_count 
FROM folders 
WHERE doctor_id = 42; -- New doctor ID

-- They should match!
```

#### 3. Verify Schedules Duplicated
```sql
-- Get source doctor schedules count
SELECT COUNT(*) as source_count 
FROM schedules 
WHERE doctor_id = 5; -- Source doctor ID

-- Get new doctor schedules count
SELECT COUNT(*) as target_count 
FROM schedules 
WHERE doctor_id = 42; -- New doctor ID

-- They should match!
```

#### 4. Verify Placeholders Duplicated
```sql
-- Source placeholders
SELECT id, name, description 
FROM placeholders 
WHERE doctor_id = 5 
ORDER BY id;

-- Target placeholders
SELECT id, name, description 
FROM placeholders 
WHERE doctor_id = 42 
ORDER BY id;
```

#### 5. Verify Attributes Duplicated (with doctor_id)
```sql
-- Count attributes for each placeholder and verify doctor_id
SELECT p.doctor_id, p.name as placeholder_name, 
       COUNT(a.id) as attribute_count,
       COUNT(CASE WHEN a.doctor_id = p.doctor_id THEN 1 END) as correct_doctor_count
FROM placeholders p
LEFT JOIN attributes a ON p.id = a.placeholder_id
WHERE p.doctor_id IN (5, 42)
GROUP BY p.doctor_id, p.id, p.name
ORDER BY p.doctor_id, p.name;

-- All attributes should have correct doctor_id
```

#### 6. Verify Templates Duplicated
```sql
-- Source templates
SELECT id, name, folder_id, doctor_id 
FROM templates 
WHERE doctor_id = 5;

-- Target templates
SELECT id, name, folder_id, doctor_id 
FROM templates 
WHERE doctor_id = 42;
```

#### 7. Verify Template-Placeholder Associations
```sql
-- Source associations
SELECT pt.template_id, t.name as template_name, pt.placeholder_id, p.name as placeholder_name
FROM placeholder_templates pt
JOIN templates t ON pt.template_id = t.id
JOIN placeholders p ON pt.placeholder_id = p.id
WHERE t.doctor_id = 5;

-- Target associations (should have same structure with new IDs)
SELECT pt.template_id, t.name as template_name, pt.placeholder_id, p.name as placeholder_name
FROM placeholder_templates pt
JOIN templates t ON pt.template_id = t.id
JOIN placeholders p ON pt.placeholder_id = p.id
WHERE t.doctor_id = 42;
```

#### 8. Verify Medication Favorites Duplicated
```sql
-- Source favorites
SELECT mdf.id, m.name as medication_name, mdf.doctor_id
FROM medication_doctor_favorats mdf
JOIN medications m ON mdf.medication_id = m.id
WHERE mdf.doctor_id = 5;

-- Target favorites
SELECT mdf.id, m.name as medication_name, mdf.doctor_id
FROM medication_doctor_favorats mdf
JOIN medications m ON mdf.medication_id = m.id
WHERE mdf.doctor_id = 42;
```

### 📊 Complete Verification Script

```sql
-- Run this to compare source and target doctor
SET @source_doctor_id = 5;
SET @target_doctor_id = 42;

SELECT 
    'Folders' as entity,
    (SELECT COUNT(*) FROM folders WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM folders WHERE doctor_id = @target_doctor_id) as target_count;

SELECT 
    'Schedules' as entity,
    (SELECT COUNT(*) FROM schedules WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM schedules WHERE doctor_id = @target_doctor_id) as target_count;

SELECT 
    'Appointment Available Months' as entity,
    (SELECT COUNT(*) FROM appointment_available_month WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM appointment_available_month WHERE doctor_id = @target_doctor_id) as target_count;

SELECT 
    'Appointment Forcers' as entity,
    (SELECT COUNT(*) FROM appointment_forcers WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM appointment_forcers WHERE doctor_id = @target_doctor_id) as target_count;

SELECT 
    'Excluded Dates' as entity,
    (SELECT COUNT(*) FROM excluded_dates WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM excluded_dates WHERE doctor_id = @target_doctor_id) as target_count;

SELECT 
    'Placeholders' as entity,
    (SELECT COUNT(*) FROM placeholders WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM placeholders WHERE doctor_id = @target_doctor_id) as target_count;

SELECT 
    'Templates' as entity,
    (SELECT COUNT(*) FROM templates WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM templates WHERE doctor_id = @target_doctor_id) as target_count;

SELECT 
    'Medication Favorites' as entity,
    (SELECT COUNT(*) FROM medication_doctor_favorats WHERE doctor_id = @source_doctor_id) as source_count,
    (SELECT COUNT(*) FROM medication_doctor_favorats WHERE doctor_id = @target_doctor_id) as target_count;
```

**Expected Output:**
```
+------------------------------+--------------+--------------+
| entity                       | source_count | target_count |
+------------------------------+--------------+--------------+
| Folders                      |            5 |            5 |
| Schedules                    |           10 |           10 |
| Appointment Available Months |           12 |           12 |
| Appointment Forcers          |            3 |            3 |
| Excluded Dates               |            5 |            5 |
| Placeholders                 |           12 |           12 |
| Templates                    |            8 |            8 |
| Medication Favorites         |           45 |           45 |
+------------------------------+--------------+--------------+
```

### 🧾 Test 3: Folder and Placeholder ID Mapping

#### Test Folder Mapping
```sql
-- Get a template from source doctor with folder reference
SELECT t.id as template_id, t.name, t.folder_id, f.name as folder_name
FROM templates t
JOIN folders f ON t.folder_id = f.id
WHERE t.doctor_id = 5
LIMIT 1;

-- Find corresponding template in target doctor (same name)
SELECT t.id as template_id, t.name, t.folder_id, f.name as folder_name
FROM templates t
JOIN folders f ON t.folder_id = f.id
WHERE t.doctor_id = 42 
  AND t.name = '[NAME FROM ABOVE]'
LIMIT 1;
```

**Verify:**
- ✅ Template names match
- ✅ Folder names match
- ✅ Folder IDs are different (mapped correctly)
- ✅ Both folders belong to their respective doctors

#### Test Placeholder Mapping

#### Test Placeholder Mapping

**Verify Template References Updated:**

```sql
-- Get a template from source doctor
SELECT t.id as template_id, t.name, pt.placeholder_id, p.name as placeholder_name
FROM templates t
JOIN placeholder_templates pt ON t.id = pt.template_id
JOIN placeholders p ON pt.placeholder_id = p.id
WHERE t.doctor_id = 5
LIMIT 1;

-- Find corresponding template in target doctor (same name)
SELECT t.id as template_id, t.name, pt.placeholder_id, p.name as placeholder_name
FROM templates t
JOIN placeholder_templates pt ON t.id = pt.template_id
JOIN placeholders p ON pt.placeholder_id = p.id
WHERE t.doctor_id = 42 
  AND t.name = '[NAME FROM ABOVE]'
LIMIT 1;
```

**Verify:**
- ✅ Template names match
- ✅ Placeholder names match
- ✅ Placeholder IDs are different (mapped correctly)
- ✅ Both placeholders belong to their respective doctors

#### Test Attribute doctor_id
```sql
-- Verify all attributes have correct doctor_id
SELECT a.id, a.name, a.doctor_id, p.doctor_id as placeholder_doctor_id,
       CASE WHEN a.doctor_id = p.doctor_id THEN 'OK' ELSE 'MISMATCH' END as status
FROM attributes a
JOIN placeholders p ON a.placeholder_id = p.id
WHERE p.doctor_id = 42;
```

**Verify:**
- ✅ All attributes show status 'OK'
- ✅ doctor_id matches placeholder's doctor_id

### 🧾 Test 4: Error Handling

#### Test Duplicate Email
```bash
curl -X POST http://localhost/api/doctors/5/duplicate \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Duplicate Again",
    "email": "test.duplicate@hospital.com",
    "password": "Pass123"
  }'
```

**Expected:** 422 Validation Error (email already exists)

#### Test Invalid Source Doctor
```bash
curl -X POST http://localhost/api/doctors/9999/duplicate \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Test",
    "email": "new.test@hospital.com",
    "password": "Pass123"
  }'
```

**Expected:** 404 Not Found

### 📝 Log Verification

Check the Laravel logs for confirmation:

```bash
tail -f storage/logs/laravel.log | grep -i "duplication"
```

**Expected Log Entries:**
```
[INFO] Doctor duplication started for doctor ID: 5
[INFO] Consultation configurations duplicated: 5 folders, 12 sections, 8 templates, 45 medication favorites
[INFO] Doctor duplicated successfully: New doctor ID 42
```

### 🎯 Frontend Testing (Optional)

1. Navigate to the doctors list page
2. Click the "Duplicate" button on a doctor row
3. Fill in the modal:
   - Name: "Dr. Frontend Test"
   - Email: "frontend.test@hospital.com"
   - Password: "Test123"
   - Phone: "+1234567890"
4. Click "Duplicate Doctor"
5. Verify success message appears
6. Verify new doctor appears in the list

### ✅ Success Criteria

All of the following should be true:

- [ ] New doctor created with correct credentials
- [ ] All folders duplicated (same count as source)
- [ ] All schedules duplicated (same count as source)
- [ ] All appointment available months duplicated
- [ ] All appointment forcers duplicated (if any)
- [ ] All excluded dates duplicated (if any)
- [ ] All placeholders duplicated with correct names
- [ ] All placeholder attributes duplicated with correct doctor_id
- [ ] All templates duplicated with correct names and folder references
- [ ] Template-placeholder associations use new placeholder IDs
- [ ] All medication favorites duplicated
- [ ] No errors in Laravel logs
- [ ] Doctor availability cache cleared

### 🐛 Troubleshooting

#### Issue: Placeholders not duplicated

**Check:**
```sql
SELECT * FROM placeholders WHERE doctor_id = 5;
```

If empty, source doctor has no placeholders. This is expected.

#### Issue: Template associations missing

**Check:**
```sql
-- Verify placeholder_templates table exists
SHOW TABLES LIKE 'placeholder_templates';

-- Check if source has associations
SELECT COUNT(*) FROM placeholder_templates pt
JOIN templates t ON pt.template_id = t.id
WHERE t.doctor_id = 5;
```

#### Issue: Transaction rollback

**Check logs:**
```bash
grep "Doctor duplication failed" storage/logs/laravel.log -A 5
```

Common causes:
- Unique constraint violation (duplicate email)
- Foreign key constraint (invalid folder_id in templates)
- Database connection issues

### 📈 Performance Testing

For large configurations:

```bash
# Time the duplication
time curl -X POST http://localhost/api/doctors/5/duplicate \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Performance Test",
    "email": "perf.test@hospital.com",
    "password": "Test123"
  }'
```

**Expected:** < 5 seconds for doctors with ~100 total records across all relationships

## Summary

This testing guide covers:
- ✅ Complete API testing
- ✅ Database verification queries
- ✅ Placeholder ID mapping verification
- ✅ Error handling tests
- ✅ Log verification
- ✅ Frontend testing (optional)
- ✅ Performance benchmarking

Run through this checklist to ensure the doctor duplication feature works correctly with all consultation configurations.
