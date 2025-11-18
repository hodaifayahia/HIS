# Admission Form - Doctor Selection Visual Guide

## Form Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                  ADMISSION CREATION MODAL                       │
│          [Gradient Header: Indigo to Purple]                    │
│    🎯 Create New Admission                                      │
│    Register a new patient admission to the system              │
└─────────────────────────────────────────────────────────────────┘

STEP 1: Patient Selection
┌─────────────────────────────────────────────────────────────────┐
│ 👤 Patient *                                                     │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ [Search patient by name or phone...]                        │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ✅ Selected: John Doe                                           │
│ (Auto-creates fiche navette for today)                         │
└─────────────────────────────────────────────────────────────────┘

STEP 2: Admission Type Selection
┌─────────────────────────────────────────────────────────────────┐
│ 🏥 Admission Type *                                              │
│ ┌──────────────────────┐  ┌──────────────────────┐              │
│ │ 🛡️  Surgery (Upfront) │  │ ❤️  Nursing (Pay After)│             │
│ │  Surgical...         │  │  Medical care        │             │
│ └──────────────────────┘  └──────────────────────┘             │
│                                                                  │
│ (If Surgery selected ⬇️)                                        │
└─────────────────────────────────────────────────────────────────┘

STEP 3: Doctor Selection (Surgery only)
┌─────────────────────────────────────────────────────────────────┐
│ 👨‍⚕️  Doctor *                                                     │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ 👨‍⚕️  Dr. Ahmed Hassan (Cardiology)             [v]          │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ Dropdown Options:                                               │
│ • 👨‍⚕️  Dr. Ahmed Hassan (Cardiology)                           │
│ • 👨‍⚕️  Dr. Fatima Medina (Neurology)                           │
│ • 👨‍⚕️  Dr. Mohamed Ali (Surgery)                               │
│                                                                  │
│ (After doctor selection, prestation search becomes active ⬇️)  │
└─────────────────────────────────────────────────────────────────┘

STEP 4: Prestation Selection (Surgery only, filtered by specialty)
┌─────────────────────────────────────────────────────────────────┐
│ ➕ Initial Prestation *                                          │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ [Search prestation for Cardiology...]                       │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ Search Results (Filtered by Cardiology):                        │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Echocardiography                      | 🏷️ ECH | 💰 $150    │ │
│ │ Cardiac Catheterization               | 🏷️ CC  | 💰 $500    │ │
│ │ Electrocardiogram (ECG)               | 🏷️ ECG | 💰 $50     │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ ✅ Selected: Echocardiography (150 DA)                          │
└─────────────────────────────────────────────────────────────────┘

STEP 5: Consultation Reference (Auto-created)
┌─────────────────────────────────────────────────────────────────┐
│ 📄 Consultation Reference: Auto-created for today              │
│                                                                  │
│ Fiche Navette ID: #2024-11-15-001                              │
│ Status: Active                                                   │
└─────────────────────────────────────────────────────────────────┘

STEP 6: Submit
┌─────────────────────────────────────────────────────────────────┐
│                     [Cancel]  [✅ Create Admission]             │
└─────────────────────────────────────────────────────────────────┘
```

## UI Component Hierarchy

```
AdmissionCreateModal
├── Dialog Header
│   └── Gradient Background (Indigo → Purple)
│       ├── Icon Container
│       │   └── 🎯 Icon
│       └── Text
│           ├── Title: "Create New Admission"
│           └── Subtitle: "Register a new patient admission..."
│
├── Dialog Content (Form)
│   ├── Patient Selection Section
│   │   ├── Label (with 👤 icon)
│   │   ├── PatientSearch Component
│   │   ├── Success Card (when selected)
│   │   └── Error Text (if validation fails)
│   │
│   ├── Admission Type Section
│   │   ├── Label (with 🏥 icon)
│   │   ├── Radio Button: Surgery
│   │   │   └── Card Style with Icon
│   │   ├── Radio Button: Nursing
│   │   │   └── Card Style with Icon
│   │   └── Error Text (if validation fails)
│   │
│   ├── Doctor Section (Conditional: v-if="form.type === 'surgery'")
│   │   ├── Label (with 👨‍⚕️ icon)
│   │   ├── PrimeVue Dropdown
│   │   │   ├── Option Template (with icon)
│   │   │   │   ├── Doctor Name
│   │   │   │   └── Specialization
│   │   │   └── Value Template (selected display)
│   │   ├── Loading Indicator
│   │   └── Error Text (if validation fails)
│   │
│   ├── Prestation Section (Conditional: depends on doctor)
│   │   ├── Label (with ➕ icon)
│   │   ├── Warning Card (if no doctor selected)
│   │   ├── PrestationSearch Component
│   │   │   └── Props:
│   │   │       ├── specializationFilter (doctor's specialization ID)
│   │   │       └── placeholder (doctor's specialization name)
│   │   ├── Success Card (when selected)
│   │   └── Error Text (if validation fails)
│   │
│   └── Fiche Navette Info Section
│       └── Info Card (with 📄 icon)
│           ├── Reference Number
│           └── Auto-created Notice
│
└── Dialog Footer
    ├── Cancel Button (Secondary)
    └── Create Admission Button (Success)
```

## State Management

```javascript
// Form Data
{
  patient_id: '',              // Patient selection
  doctor_id: '',               // Doctor selection (surgery only)
  type: 'nursing',             // 'surgery' or 'nursing'
  initial_prestation_id: '',   // Prestation selection (surgery only)
  fiche_navette_id: ''         // Auto-set after patient selection
}

// UI State
{
  loading: false,              // Form submission
  loadingDoctors: false,       // Doctor fetch
  creatingFiche: false,        // Fiche creation
  errors: {}                   // Validation errors
}

// Selection State
{
  selectedPatient: null,       // Full patient object
  selectedDoctor: null,        // Full doctor object with specialization
  selectedPrestation: null,    // Full prestation object
  currentFicheNavette: null    // Auto-created fiche object
}

// Search State
{
  patientSearchValue: '',      // Patient search query
  prestationSearchValue: ''    // Prestation search query
}
```

## Doctor-Specialization-Prestation Relationship

```
Doctor
├── id: 1
├── user_id: 5
├── specialization_id: 2
└── specialization: {
    id: 2,
    name: "Cardiology"
  }

        ↓ (filtered by specialization_id)

Prestation (filtered results)
├── id: 10
├── name: "Electrocardiogram"
├── specialization_id: 2
└── price_with_vat_and_consumables_variant: 150

Prestation (filtered results)
├── id: 11
├── name: "Echocardiography"
├── specialization_id: 2
└── price_with_vat_and_consumables_variant: 500

        ↓ (user selects)

ficheNavetteItem (created in AdmissionService)
├── fiche_navette_id: 1
├── prestation_id: 11
├── patient_id: 1
├── base_price: 500
├── final_price: 500
└── status: 'active'
```

## API Request/Response Flow

### 1. Load Doctors
```
GET /api/doctors

Response:
[
  {
    id: 1,
    user: { id: 5, name: "Dr. Ahmed Hassan" },
    specialization: { id: 2, name: "Cardiology" }
  },
  ...
]
```

### 2. Search Prestations (with Filter)
```
GET /api/prestation?search=Echo&specialization_id=2

Response:
[
  {
    id: 10,
    name: "Electrocardiogram (ECG)",
    code: "ECG",
    price_with_vat_and_consumables_variant: 150,
    specialization: { id: 2, name: "Cardiology" }
  },
  {
    id: 11,
    name: "Echocardiography",
    code: "ECHO",
    price_with_vat_and_consumables_variant: 500,
    specialization: { id: 2, name: "Cardiology" }
  }
]
```

### 3. Create Admission
```
POST /api/admissions

Request:
{
  patient_id: 1,
  doctor_id: 1,
  type: "surgery",
  initial_prestation_id: 11,
  fiche_navette_id: 1
}

Response:
{
  data: {
    id: 5,
    patient_id: 1,
    doctor_id: 1,
    type: "surgery",
    fiche_navette_id: 1,
    status: "admitted",
    created_at: "2024-11-15T10:30:00Z"
  }
}
```

## Color Scheme Reference

| Component | Color | Tailwind Class | Usage |
|-----------|-------|---|---|
| Header Background | Indigo→Purple | `tw-from-indigo-600 tw-to-purple-700` | Main header |
| Doctor Icon | Indigo | `tw-text-indigo-600` | Doctor label icon |
| Surgery Type | Amber | `tw-text-amber-600` | Surgery option |
| Nursing Type | Green | `tw-text-green-500` | Nursing option |
| Prestation Icon | Emerald | `tw-text-emerald-600` | Prestation label icon |
| File Icon | Blue | `tw-text-blue-600` | Fiche reference icon |
| Info Background | Blue-50 | `tw-from-blue-50 tw-to-indigo-50` | Info card |
| Success | Green | `tw-bg-green-50 tw-border-green-200` | Success card |
| Warning | Amber | `tw-bg-amber-50 tw-border-amber-200` | Warning card |

## Icons Used

| Icon | Component | Source |
|------|-----------|--------|
| 🎯 | Header | `pi pi-plus-circle` (PrimeVue) |
| 👤 | Patient | `pi pi-user` (PrimeVue) |
| 🏥 | Admission Type | `pi pi-building` (PrimeVue) |
| 🛡️ | Surgery | (Unicode) |
| ❤️ | Nursing | (Unicode) |
| 👨‍⚕️ | Doctor | `pi pi-user-md` (PrimeVue) |
| ➕ | Prestation | `pi pi-plus-circle` (PrimeVue) |
| 📄 | File | `pi pi-file-pdf` (PrimeVue) |
| ✅ | Checkmark | `pi pi-check-circle` (PrimeVue) |

---

**Last Updated**: November 15, 2025
