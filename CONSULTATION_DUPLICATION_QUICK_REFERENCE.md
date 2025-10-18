# Quick Reference: Consultation Configuration Duplication

## 🎯 Quick Start

### Duplicate a Section (with attributes)
```bash
POST /api/placeholders/{id}/duplicate
{
  "target_doctor_id": 5,
  "new_name": "Optional new name"
}
```

### Duplicate a Template
```bash
POST /api/templates/{id}/duplicate
{
  "target_doctor_id": 5,
  "folder_id": 3,
  "new_name": "Optional new name"
}
```

### Copy All Medication Favorites
```bash
POST /api/medication-favorites/duplicate
{
  "source_doctor_id": 3,
  "target_doctor_id": 5
}
```

---

## 📋 What Gets Copied

### Sections/Placeholders ✅
- Section name & description
- Specialization link
- All attributes:
  - Names
  - Descriptions
  - Default values
  - Input types
  - Required flags

### Templates ✅
- Template name & description
- Full HTML content
- Document format type
- All placeholder associations
- Custom placeholder attributes

### Medication Favorites ✅
- All favorited medications
- Auto-skips existing favorites
- Preserves medication metadata

---

## 🔄 Common Workflows

### **Scenario 1: New Doctor Onboarding**
Copy everything from experienced doctor to new doctor:

```javascript
// 1. Copy sections
const sections = await getSections(sourceDoctorId);
for (const section of sections) {
  await axios.post(`/api/placeholders/${section.id}/duplicate`, {
    target_doctor_id: newDoctorId
  });
}

// 2. Copy templates
const templates = await getTemplates(sourceDoctorId);
for (const template of templates) {
  await axios.post(`/api/templates/${template.id}/duplicate`, {
    target_doctor_id: newDoctorId,
    folder_id: targetFolderId
  });
}

// 3. Copy medication favorites
await axios.post('/api/medication-favorites/duplicate', {
  source_doctor_id: sourceDoctorId,
  target_doctor_id: newDoctorId
});
```

### **Scenario 2: Create Specialization-Specific Template**
```javascript
// Duplicate section with custom name for different specialization
await axios.post(`/api/placeholders/${sectionId}/duplicate`, {
  target_doctor_id: sameDoctorId,
  new_name: "Physical Exam - Cardiology"
});
```

### **Scenario 3: Backup Configuration**
```javascript
// Duplicate to backup doctor account
const backupDoctorId = 999;

// Copy all sections
sections.forEach(s => duplicateSection(s.id, backupDoctorId));

// Copy all templates
templates.forEach(t => duplicateTemplate(t.id, backupDoctorId, backupFolderId));

// Copy favorites
duplicateFavorites(currentDoctorId, backupDoctorId);
```

---

## ⚠️ Important Notes

### Naming Conflicts
- Templates default to "{original name} (Copy)"
- Sections keep original name unless `new_name` provided
- **Recommendation:** Always provide meaningful names

### Folder Requirements
- Templates MUST specify `folder_id`
- Ensure folder exists for target doctor
- Create folders first if needed

### Medication Favorites
- Automatically skips duplicates
- Returns count of:
  - Total source favorites
  - Successfully duplicated
  - Skipped (already exist)

### Transaction Safety
- All operations use database transactions
- Rollback on any error
- Partial duplication prevented

---

## 🧪 Testing Checklist

- [ ] Section duplication creates all attributes
- [ ] Template duplication preserves placeholder links
- [ ] Medication favorites skip existing entries
- [ ] Error handling for invalid doctor IDs
- [ ] Error handling for missing folders
- [ ] Transaction rollback on failure
- [ ] Proper response messages
- [ ] Database foreign key integrity

---

## 🐛 Troubleshooting

### "Target doctor not found"
✅ Verify doctor ID exists in database
✅ Check if doctor account is active

### "Folder not found"
✅ Create folder first for target doctor
✅ Use correct folder ID in request

### "Template duplication failed"
✅ Check if all placeholders still exist
✅ Verify placeholder-template associations
✅ Review logs for specific error

### Partial Data Copied
✅ Should NOT happen (transactions)
✅ If it does, check database transaction support
✅ Review error logs

---

## 📊 API Response Examples

### Success Response
```json
{
  "success": true,
  "message": "Section duplicated successfully",
  "data": {
    "id": 42,
    "name": "New Section",
    "attributes": [...]
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Failed to duplicate: Target doctor not found"
}
```

### Medication Favorites Response
```json
{
  "message": "Favorite medications duplicated successfully",
  "total_source_favorites": 45,
  "duplicated_count": 42,
  "skipped_count": 3
}
```

---

## 🎨 Frontend Integration (Coming Soon)

### Placeholder for UI Components
```vue
<!-- Section duplication button -->
<button @click="duplicateSection(section.id)">
  <i class="fas fa-copy"></i> Duplicate Section
</button>

<!-- Template duplication modal -->
<DuplicateTemplateModal
  :template="selectedTemplate"
  @duplicated="handleDuplicated"
/>

<!-- Bulk favorites copy -->
<button @click="copyAllFavorites()">
  Copy All Medications from Dr. Smith
</button>
```

---

## 📚 Related Documentation
- See `CONSULTATION_DUPLICATION_FEATURE.md` for full details
- See `DOCTOR_DUPLICATION_FEATURE.md` for doctor cloning
- API documentation: Check Postman collection

---

## ✅ Quick Validation

After duplication, verify:
1. New record created in target doctor's list
2. All child records (attributes/placeholders) present
3. Original data unchanged
4. Proper foreign key relationships
5. No orphaned records

---

**Last Updated:** October 14, 2025
**Version:** 1.0.0
