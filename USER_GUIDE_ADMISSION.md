# USER GUIDE - Admission Workflow (Updated)

## 🎯 What's New?

The admission creation process is now **completely automated**:
1. Patient search now shows if they have today's fiche navette
2. Fiche navette is automatically created/linked during admission creation
3. Initial prestation is automatically added for Surgery type only

---

## 👥 How to Create an Admission

### Step 1: Navigate to Admissions
```
Menu → Admin → Admissions → Create New Admission
```

### Step 2: Search for Patient
```
Field: "Search Patient"
Action: Type patient name, phone, or ID

Example:
  Type: "nath"
  Results show:
    - Nathalie Hoareau (Phone: 02 93 37 07 26)
    - ✅ Today's Fiche: FN-1-20251115... (if exists)
```

### Step 3: Select Patient from Results
```
Click on patient name in dropdown
→ Patient card appears showing:
   - Patient name
   - Phone number
   - ID number
   - Today's fiche info (if exists)
   - Clear button (to change selection)
```

### Step 4a: Surgery Admission (Upfront Payment)
```
1. Select Type: Click "Surgery (Upfront)" button
2. Select Doctor: Choose from dropdown (optional)
3. Select Initial Prestation: **REQUIRED** - Choose from dropdown
   - Example: "General Consultation"
   - System will auto-add this to fiche
4. Read Warning:
   "Surgery Admission: The selected initial prestation 
    will be automatically added to the fiche navette."
5. Click "Create Admission"

Result:
  ✅ Fiche navette created or linked
  ✅ Initial prestation added as item
  ✅ Fiche total amount calculated
  ✅ Admission created and linked
  ✅ Success message shown
```

### Step 4b: Nursing Admission (Pay After Services)
```
1. Select Type: Click "Nursing (Pay After)" button
2. Select Doctor: Choose from dropdown (optional)
3. Note: Initial Prestation field is NOT shown
   - Reason: Nursing doesn't need default prestation
4. Read Info:
   "Nursing Admission: No default prestation will be added. 
    Services will be added as needed."
5. Click "Create Admission"

Result:
  ✅ Fiche navette created or linked
  ✅ NO prestation added
  ✅ Fiche is empty (ready for services)
  ✅ Admission created and linked
  ✅ Success message shown
```

### Step 5: Success
```
After clicking "Create Admission":

Success Message:
"Admission created successfully. 
 Fiche Navette has been automatically created/linked."

→ Redirect to admission detail page
```

---

## 💡 Understanding the Types

### Surgery (Upfront) 💰
- **Payment Model**: Customer pays before treatment
- **Initial Prestation**: REQUIRED - Must select one
- **Auto-Added**: Yes - Prestation automatically added to fiche
- **Pricing**: Calculated from prestation (includes VAT + consumables)
- **Use Case**: Planned surgeries, procedures with known cost

### Nursing (Pay After) 👨‍⚕️
- **Payment Model**: Customer pays after receiving services
- **Initial Prestation**: NOT NEEDED - No default
- **Auto-Added**: No - Services added manually later
- **Pricing**: 0 initially, added as services provided
- **Use Case**: General care, ongoing treatment, unknown duration

---

## 📊 What Happens Behind the Scenes

### When You Search for a Patient
```
User Types: "nath"
    ↓
System Queries:
  - Find all patients with "nath" in name/phone/ID
  - For each patient, check if they have today's fiche navette
  - Return results with fiche info
    ↓
Display:
  Nathalie Hoareau
  Phone: 02 93 37 07 26
  ✅ Today's Fiche: ID 3 (if exists)
  OR
  (no fiche indicator) (if none exist)
```

### When You Create Surgery Admission
```
User Clicks "Create Admission"
    ↓
System Checks:
  1. Does patient have today's fiche?
     YES → Reuse it
     NO → Create new one
    ↓
  2. Add initial prestation:
     - Get prestation details
     - Calculate price (includes VAT + consumables)
     - Add as item to fiche
     - Update fiche total amount
    ↓
  3. Create admission record
     - Link to fiche navette
     - Store initial prestation ID
     - Set status to "admitted"
    ↓
Success Message:
"Admission created successfully. 
 Fiche Navette has been automatically created/linked."
    ↓
Redirect to admission detail page
```

### When You Create Nursing Admission
```
User Clicks "Create Admission"
    ↓
System Checks:
  1. Does patient have today's fiche?
     YES → Reuse it
     NO → Create new one
    ↓
  2. NO prestation added
     (Fiche stays empty)
    ↓
  3. Create admission record
     - Link to fiche navette
     - No initial prestation ID
     - Set status to "admitted"
    ↓
Success Message:
"Admission created successfully. 
 Fiche Navette has been automatically created/linked."
    ↓
Redirect to admission detail page
```

---

## 🔄 Common Scenarios

### Scenario 1: Same Patient Called Twice (Same Day)

**Morning - Surgery Admission**:
```
Patient: Nathalie
Type: Surgery
Prestation: General Consultation

Result:
  ✅ Fiche created (ID: 3)
  ✅ Prestation added (ECG)
  ✅ Admission created (ID: 100)
```

**Afternoon - Nursing Admission**:
```
Patient: Nathalie
Type: Nursing

System notices: Nathalie already has fiche today!
Result:
  ✅ SAME Fiche reused (ID: 3)
  ✅ No new prestation added
  ✅ Admission created (ID: 101)

Fiche now has:
  - 1 item (ECG from surgery)
  - 2 admissions linked
  - NOT duplicated
```

### Scenario 2: Same Patient Called Tomorrow

**Today - Admission Created**:
```
Patient: Nathalie
Fiche created for today (ID: 3)
```

**Tomorrow - New Admission**:
```
Patient: Nathalie
System checks: Does Nathalie have TOMORROW's fiche?
Result:
  ❌ No - she only has TODAY's fiche
  ✅ NEW Fiche created for tomorrow (ID: 5)
  ✅ Admission created

Fiches:
  - Today's fiche (ID: 3) - Previous admissions
  - Tomorrow's fiche (ID: 5) - New admissions
```

### Scenario 3: Adding Services Later

**Initial Admission (Nursing)**:
```
Type: Nursing
Result:
  - Fiche created
  - NO prestation
  - Fiche total: $0
```

**Later (From Admission Detail Page)**:
```
User clicks: "Add Service"
Select: Lab Work ($25)
        Imaging ($50)

Result:
  - Items added to SAME fiche
  - Fiche total updated: $75
  - Patient billed when ready
```

---

## ✅ Checklist for Different Scenarios

### ✅ For Surgery Admissions:
- [ ] Confirm patient selected
- [ ] Type = "Surgery (Upfront)" selected
- [ ] Initial Prestation selected
- [ ] Doctor selected (optional but recommended)
- [ ] Click "Create Admission"
- [ ] Success message appears
- [ ] Verify prestation added in admission detail

### ✅ For Nursing Admissions:
- [ ] Confirm patient selected
- [ ] Type = "Nursing (Pay After)" selected
- [ ] NO Initial Prestation field shown (normal)
- [ ] Doctor selected (optional but recommended)
- [ ] Click "Create Admission"
- [ ] Success message appears
- [ ] Add services from admission detail as needed

### ✅ For Repeat Patients:
- [ ] Search for patient
- [ ] Check if "Today's Fiche" indicator shows
- [ ] System will reuse existing fiche (no duplicate)
- [ ] Proceed with new admission normally

---

## 🔍 How to Verify Everything Worked

### Check Admission Detail Page
```
Navigate to: Admin → Admissions → [Admission ID]

Verify:
  ✅ Fiche Navette ID is populated (not empty)
  ✅ Type shows correct value (surgery/nursing)
  ✅ Status is "admitted"
  ✅ For surgery: Initial Prestation ID shows value
  ✅ For nursing: Initial Prestation ID is empty
```

### Check Fiche Navette
```
Navigate to: Admin → Fiche Navettes → [Fiche ID]

Verify for Surgery:
  ✅ Items count = 1 or more
  ✅ First item = initial prestation
  ✅ Item price calculated correctly
  ✅ Total amount updated

Verify for Nursing:
  ✅ Items count = 0 initially
  ✅ Total amount = $0
  ✅ Ready to add services
```

---

## ⚠️ Important Notes

### Rule 1: Surgery Requires Initial Prestation
```
If you try to create Surgery admission without prestation:
ERROR: "Initial prestation is required for surgery admission"
Solution: Select a prestation from the dropdown
```

### Rule 2: Patient Must Be Selected
```
Submit button is DISABLED until patient selected
Action: Click on patient from search results
```

### Rule 3: Fiche is Automatically Linked
```
You don't need to manually select a fiche
System handles it automatically:
  - Finds today's fiche if exists
  - Creates new one if needed
```

### Rule 4: Same Day = Same Fiche
```
Multiple admissions for same patient, same day:
  → All linked to SAME fiche
  → Fiche is NOT duplicated
  → Items accumulate in same fiche
```

---

## 🆘 Troubleshooting

### Issue: Prestation field not showing (for Surgery)
```
Problem: I selected Surgery but no prestation field appears

Solution:
  1. Check if "Surgery (Upfront)" button is selected
  2. Click it again to ensure it's selected
  3. Scroll down - field might be below
  4. Refresh page if needed
```

### Issue: Can't select patient
```
Problem: Patient list is empty or not showing

Solution:
  1. Type in search field (don't rely on dropdown)
  2. Start typing patient name, phone, or ID
  3. Results should appear as you type
  4. If no results, patient might not exist in system
```

### Issue: Submit button disabled
```
Problem: "Create Admission" button is grayed out

Solution:
  1. Click on patient from search results
  2. Patient card must appear
  3. THEN submit button becomes enabled
  4. Select admission type and submit
```

### Issue: Success message but fiche empty
```
Problem: Fiche created but no prestation added

Possible Causes:
  1. You selected Nursing (which is correct - no prestation)
  2. Surgery without selecting prestation (not possible - error)
  3. System issue - check logs

Solution:
  For Nursing: Normal - add services from detail page
  For other issues: Contact support
```

---

## 📞 Need Help?

**For Patient Search Issues**:
- Verify patient exists in system
- Try searching with different fields (name vs phone vs ID)

**For Fiche Issues**:
- Check admission detail page
- Verify fiche_navette_id is not null/empty
- Contact support if missing

**For Prestation Issues**:
- Verify prestation exists and is active
- Check prestation has valid pricing
- Contact support if unable to select

---

## ✨ Summary

**What Changed**:
- Fiche navette now created automatically ✅
- Patient search shows today's fiche info ✅
- Initial prestation auto-added for surgery ✅
- Nursing admissions don't get default prestation ✅

**What's Better**:
- Faster admission creation ✅
- No manual fiche selection needed ✅
- Automatic reuse of today's fiche ✅
- Type-specific behavior (surgery vs nursing) ✅

**Ready to Use** ✨
