# 🏥 HIS Admission System - Quick Reference v2

**Version**: 2.0 (Clarified)  
**Last Updated**: November 13, 2025

---

## ⚡ 5-Minute Overview

### What Changed from v1?

| Item | v1 | v2 |
|------|----|----|
| Models | 9 | **7** |
| Medication tracking | Separate model | **Consumption only** |
| Pricing fields | In Admission | **Only in ficheNavette** |
| Surgery/Nursing | 2 separate models | **1 type field** |
| Lines of code | ~1000 | **~600** |

---

## 🎯 Core Concept: Type-Based Admission

```
Admission Model
├─ type: 'surgery'  → Requires upfront payment
└─ type: 'nursing'  → Pay after services
```

### The ONLY Difference

| Aspect | Surgery | Nursing |
|--------|---------|---------|
| **Upfront Payment** | ✅ REQUIRED | ❌ NOT REQUIRED |
| **Initial Prestation** | ✅ Must define | ❌ Optional |
| **Medication Cost** | Tracked during | Tracked during |
| **When Paid** | Before delivery | After delivery |

---

## 💊 Medication Rule: > 5000 DA

### The Logic

```
Patient gets medications during stay:
  - Medication 1: 1,000 DA
  - Medication 2: 2,000 DA
  - Medication 3: 2,500 DA
  - TOTAL: 5,500 DA (> 5000)

TRIGGER:
  → Create AdmissionProcedure
  → Set: is_medication_conversion = true
  → Mark medications as "converted"
  → Bill this as single "medication charges" line item
  → Medications now count toward remaining charges
```

### Why This Rule?

1. **Simplifies Billing** - Medications don't appear as 100 separate lines
2. **Clear Charges** - Shows patient paid for "medication services" not itemized drugs
3. **Real-World** - Large medication usage = significant procedure cost

---

## 📊 7 Core Models

### 1️⃣ **Admission** (Hub)
```php
Fields:
  - patient_id → Who
  - doctor_id → Which doctor
  - type → 'surgery' | 'nursing'
  - status → admitted | in_service | document_pending | ready_for_discharge
  - remaining_balance → What they still owe
  - admitted_at, discharged_at
```

### 2️⃣ **AdmissionProcedure** (Surgery/Nursing procedures)
```php
Fields:
  - admission_id, prestation_id
  - name, description
  - is_medication_conversion → TRUE if created from medication total
  - status → scheduled | in_progress | completed | cancelled
  - performed_by → Which staff member
```

### 3️⃣ **AdmissionMedicationConsumption** - NOT NEEDED ❌
```
Medications are tracked in ficheNavette instead
Not part of admission system
```

### 4️⃣ **AdmissionDocument** (Paperwork tracking)
```php
Fields:
  - type → consent_form | medical_history | insurance_card | etc
  - is_physical_uploaded → Document scan uploaded?
  - is_digital_verified → Matches our digital records?
  - file_path → Where we stored the scan
```

### 5️⃣ **AdmissionBillingRecord** (Invoice lines)
```php
Fields:
  - item_type → procedure | service | nursing_care (NO medication)
  - amount → How much to charge
  - is_paid → Paid yet?
  - Note: Simple tracking, payment handled separately
```

### 6️⃣ **AdmissionDischargeTicket** (Exit paperwork - Auto-generated)
```php
Fields:
  - admission_id
  - authorized_by → Which doctor signed off
  - generated_at → Auto-generated, not manual
  - ticket_number → For tracking
  - Note: System auto-generates on discharge, staff doesn't manually fill
```

### 7️⃣ **AdmissionDocumentComparison** (Discrepancy tracking - Optional)
```php
Note: Used IF document discrepancies found during verification
      Not all admissions need this model populated
Fields:
  - admission_id
  - document_type, field_name
  - status → match | mismatch | missing_physical | missing_digital
```

---

## 🔄 Quick Workflows

### Surgery Admission Flow
```
1. Create Admission (type='surgery')
2. Upload Documents
3. Select Initial Prestation
4. PAYMENT REQUIRED ← Unique to surgery
5. Perform procedures + add medications
6. IF medications > 5000 DA → Auto-create procedure
7. Verify Documents
8. Patient pays remaining balance
9. Discharge (only if balance = 0)
```

### Nursing Admission Flow
```
1. Create Admission (type='nursing')
2. Upload Documents
3. NO PAYMENT YET ← Unique to nursing
4. Add procedures only (medications in ficheNavette)
5. Verify Documents
6. Calculate final bill
7. Patient pays everything
8. Discharge (only if balance = 0)
```

---

## 📍 Integration with HIS

### ficheNavette Access
```
Admission.ficheNavettes()
  → Patient's consultations TODAY (read-only)
  → Gives context to admission
  → Doesn't interfere with admission workflow
```

### Caisse (Payment Recording)
```
AdmissionBillingService.recordPayment()
  → Creates CaisseSession entry
  → Updates remaining_balance
  → Logs transaction
```

### Prestation (Medical Services)
```
AdmissionProcedure.prestation_id
  → Links to Prestation for pricing
  → Provides service details
  → All costs from ficheNavette pricing system
```

### Patient Timeline
```
Patient.admissions()
  → All hospital stays
  → Part of patient's medical record
  → Linked to other activities (consultations, etc)
```

---

## 💡 Key Differences from Consultation (ficheNavette)

| Aspect | Consultation (ficheNavette) | Admission |
|--------|------------------------------|-----------|
| **Purpose** | One-time clinic visit | Multi-day hospital stay |
| **Duration** | Hours | Days/weeks |
| **Location** | Clinic/outpatient | Hospital ward |
| **Billing** | Immediate | Post-admission or upfront |
| **Documents** | Prescription, invoice | Consent, discharge papers |
| **Medications** | Prescribed | Consumed/tracked |
| **Type** | Single workflow | Surgery OR Nursing |

---

## ✅ Ready to Discharge?

Patient can discharge ONLY when:

```
✅ documents_verified = true
✅ remaining_balance = 0
✅ All procedures completed/cancelled
✅ Discharge ticket generated

IF remaining_balance > 0:
  → Cannot discharge
  → Show: "Patient still owes X DA"
```

---

## 🚀 What's Next?

### For Developers
```bash
# Start Phase 1
cd /home/administrator/www/HIS
php artisan make:migration create_admissions_table
php artisan make:migration create_admission_procedures_table
# ... create other migrations
```

### For Managers
- Estimated time: **15-23 hours**
- 7 phases with clear deliverables
- See ADMISSION_SYSTEM_v2.md for full breakdown

### For QA
- Test medication >5000 DA rule
- Test surgery vs nursing upfront payment
- Test discharge prerequisites
- Test document discrepancy handling

---

## 📞 Common Questions

**Q: Where is pricing stored?**  
A: All pricing in `ficheNavette` relationships. Admission just tracks what's used and links back.

**Q: Can I modify an admission after discharge?**  
A: No - discharge is final. If needed, create new admission.

**Q: What if patient can't pay upfront for surgery?**  
A: Create as 'nursing' type instead and manually override if needed. Business rules enforced at service layer.

**Q: Can medications be < 5000 DA?**  
A: Yes - they bill individually if under 5000 DA total. Conversion only triggers if > 5000 DA.

**Q: Do I need ficheNavette for admission?**  
A: No - completely independent. But you CAN view today's fiche from admission for context.

---

## 📊 Database Quick Stats

```
Total Tables: 7
Total Fields: ~80
Foreign Keys: 15+
Indexes: 20+
Soft Deletes: Yes (all tables)
Audit Trail: Via User relationships
```

---

**Status**: ✅ Ready for Implementation  
**Start**: Read ADMISSION_SYSTEM_v2.md section 6-8  
**Questions?**: See ADMISSION_SYSTEM_v2.md "Integration Points" section
