# Consultation Configuration Duplication Feature

## Overview
This feature allows doctors to duplicate their entire consultation configuration (sections, templates, and medication favorites) to other doctors or themselves. This is extremely useful when:
- Setting up a new doctor with similar workflow
- Creating backup configurations
- Standardizing consultation templates across doctors
- Migrating configurations between doctors

## Features Implemented

### 1. **Section/Placeholder Duplication** 
Duplicate consultation sections with all their attributes from one doctor to another.

**Endpoint:** `POST /api/placeholders/{placeholderId}/duplicate`

**Request:**
```json
{
  "target_doctor_id": 5,
  "new_name": "Physical Examination - Cardiology" // Optional: defaults to original name
}
```

**Response:**
```json
{
  "success": true,
  "message": "Section duplicated successfully",
  "data": {
    "id": 42,
    "name": "Physical Examination - Cardiology",
    "description": "...",
    "doctor_id": 5,
    "specializations_id": 2,
    "attributes": [...]
  }
}
```

**What Gets Copied:**
- ✅ Section name & description
- ✅ Specialization association
- ✅ All attributes with their:
  - Names
  - Descriptions
  - Default values
  - Input types (text, textarea, select, etc.)
  - Required flags

---

### 2. **Template Duplication**
Duplicate document templates with all placeholder associations from one doctor to another.

**Endpoint:** `POST /api/templates/{templateId}/duplicate`

**Request:**
```json
{
  "target_doctor_id": 5,
  "folder_id": 3,
  "new_name": "Consultation Note - Cardiology Copy" // Optional
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Template duplicated successfully",
  "data": {
    "id": 78,
    "name": "Consultation Note - Cardiology Copy",
    "content": "<!DOCTYPE html>...",
    "mime_type": "Consultation",
    "doctor_id": 5,
    "folder_id": 3,
    "placeholders": [...]
  }
}
```

**What Gets Copied:**
- ✅ Template name & description
- ✅ Full HTML content
- ✅ Document format (A4/Prescription)
- ✅ All placeholder associations
- ✅ Attribute mappings for custom placeholders

---

### 3. **Medication Favorites Duplication**
Copy all favorite medications from one doctor to another.

**Endpoint:** `POST /api/medication-favorites/duplicate`

**Request:**
```json
{
  "source_doctor_id": 3,
  "target_doctor_id": 5
}
```

**Response:**
```json
{
  "message": "Favorite medications duplicated successfully",
  "total_source_favorites": 45,
  "duplicated_count": 42,
  "skipped_count": 3
}
```

**What Gets Copied:**
- ✅ All favorited medications
- ✅ Skips duplicates (won't add if already favorited)
- ✅ Preserves medication details

---

## Database Relationships

### Tables Involved:

#### Placeholders (Sections)
```
placeholders
├── id
├── name
├── description
├── doctor_id
├── specializations_id
└── attributes
    ├── id
    ├── placeholder_id
    ├── name
    ├── description
    ├── value
    ├── input_type
    └── is_required
```

#### Templates
```
templates
├── id
├── name
├── description
├── content
├── doctor_id
├── mime_type
├── folder_id
└── placeholder_templates (pivot)
    ├── template_id
    ├── placeholder_id
    └── attribute_id
```

#### Medication Favorites
```
medication_doctor_favorats
├── id
├── medication_id
├── doctor_id
└── favorited_at
```

---

## Routes Added

### In `routes/web.php`:

```php
// Placeholders/Sections
Route::post('placeholders/{placeholderId}/duplicate', [PlaceholderController::class, 'duplicate']);

// Templates
Route::post('/templates/{templateId}/duplicate', [TemplateController::class, 'duplicate']);

// Medication Favorites
Route::post('/medication-favorites/duplicate', [MedicationDoctorFavoratController::class, 'duplicateFavorites']);
```

---

## Controllers Updated

### 1. **PlaceholderController.php**
Added `duplicate()` method:
- Validates target doctor ID
- Creates new placeholder for target doctor
- Bulk creates all attributes with proper relationships
- Uses database transaction for data integrity

### 2. **TemplateController.php**
Added `duplicate()` method:
- Validates target doctor and folder
- Creates new template with full content
- Duplicates all placeholder associations
- Uses database transaction

### 3. **MedicationDoctorFavoratController.php**
Added `duplicateFavorites()` method:
- Fetches all favorites from source doctor
- Checks for existing favorites on target
- Creates only new favorites (prevents duplicates)
- Returns detailed count statistics

---

## Usage Examples

### Example 1: Duplicate a Section to Another Doctor
```javascript
// From Vue frontend
const duplicateSection = async (sectionId) => {
  const response = await axios.post(
    `/api/placeholders/${sectionId}/duplicate`,
    {
      target_doctor_id: targetDoctorId,
      new_name: 'Cardiology Exam (Dr. Smith)'
    }
  );
  console.log('New section:', response.data.data);
};
```

### Example 2: Duplicate a Template
```javascript
const duplicateTemplate = async (templateId, targetDoctor, targetFolder) => {
  const response = await axios.post(
    `/api/templates/${templateId}/duplicate`,
    {
      target_doctor_id: targetDoctor,
      folder_id: targetFolder,
      new_name: 'Consultation Note - Copy'
    }
  );
  return response.data.data;
};
```

### Example 3: Copy All Favorites
```javascript
const copyFavorites = async (fromDoctor, toDoctor) => {
  const response = await axios.post(
    '/api/medication-favorites/duplicate',
    {
      source_doctor_id: fromDoctor,
      target_doctor_id: toDoctor
    }
  );
  console.log(`Copied ${response.data.duplicated_count} medications`);
};
```

---

## Testing Guide

### Test Section Duplication:
1. Create a section with multiple attributes
2. Call duplicate endpoint with different doctor
3. Verify all attributes are copied correctly
4. Check database for proper foreign keys

### Test Template Duplication:
1. Create a template with placeholders
2. Duplicate to another doctor/folder
3. Verify content and placeholder associations
4. Test in template editor

### Test Medication Favorites:
1. Favorite several medications as Doctor A
2. Duplicate to Doctor B
3. Verify favorites appear in Doctor B's list
4. Try duplicating again (should skip existing)

---

## Error Handling

All endpoints return proper error responses:

```json
{
  "success": false,
  "message": "Failed to duplicate section: Target doctor not found"
}
```

Common error codes:
- `400` - Invalid request (missing fields)
- `404` - Resource not found
- `500` - Server error (transaction failure)

---

## Security Considerations

- ✅ Validates target doctor exists
- ✅ Uses database transactions for consistency
- ✅ Prevents partial duplications on error
- ✅ Validates all foreign key relationships
- ⚠️ **Note:** No permission checks implemented yet
  - Consider adding: Can user duplicate to target doctor?
  - Consider adding: Can user access source data?

---

## Future Enhancements

### Possible Additions:
1. **Bulk Duplication**
   - Duplicate multiple sections at once
   - Duplicate entire doctor configuration

2. **Permission System**
   - Role-based access for duplication
   - Doctor-level approval for receiving duplicates

3. **Version Control**
   - Track duplication history
   - Revert to original configurations

4. **Smart Merging**
   - Merge configurations instead of full copy
   - Update existing vs. create new

5. **UI Components**
   - Modal for selecting what to duplicate
   - Preview before duplication
   - Batch operations interface

---

## Related Files

### Backend:
- `/app/Http/Controllers/PlaceholderController.php`
- `/app/Http/Controllers/TemplateController.php`
- `/app/Http/Controllers/MedicationDoctorFavoratController.php`
- `/app/Models/Placeholder.php`
- `/app/Models/Template.php`
- `/app/Models/MedicationDoctorFavorat.php`
- `/routes/web.php`

### Frontend (to be implemented):
- Section duplication UI in PlaceholdersList.vue
- Template duplication UI in templateList.vue
- Medication favorites duplication in MedicalesList.vue

---

## Changelog

### v1.0.0 - October 14, 2025
- ✅ Initial implementation of section duplication
- ✅ Template duplication with placeholder associations
- ✅ Medication favorites batch duplication
- ✅ Database transaction support
- ✅ Error handling and validation
- ✅ API routes registration

---

## Support

For issues or questions:
1. Check logs in `storage/logs/laravel.log`
2. Verify database relationships
3. Test with Postman/API client first
4. Check frontend console for errors

---

## Summary

This duplication feature provides a powerful way to replicate consultation configurations across doctors, significantly reducing setup time and ensuring consistency. All operations are transaction-safe and include comprehensive error handling.

**Key Benefits:**
- 🚀 Fast doctor onboarding
- 📋 Configuration standardization
- 🔄 Easy backup and migration
- ✅ Data integrity via transactions
- 📊 Detailed operation feedback
