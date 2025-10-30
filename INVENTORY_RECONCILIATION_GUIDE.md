# Inventory Audit Reconciliation Feature - Complete Implementation Guide

## 📋 Overview
This feature enables comparison of participant counts and resolution of discrepancies through a recount workflow.

## 🏗️ Architecture

### Backend Components

#### 1. **InventoryReconciliationService.php**
Location: `app/Services/Inventory/InventoryReconciliationService.php`

**Methods:**
- `analyzeDiscrepancies(InventoryAudit $audit)` - Compare all participants who have sent their counts
- `assignRecount(InventoryAudit $audit, array $productIds, int $participantId)` - Assign disputed products for recount
- `getRecountItems(InventoryAudit $audit, int $participantId)` - Get items requiring recount
- `finalizeReconciliation(InventoryAudit $audit)` - Mark audit as completed

#### 2. **Controller Methods** (InventoryAuditController.php)
- `GET /api/inventory-audits/{id}/analyze-discrepancies` - Get reconciliation analysis
- `POST /api/inventory-audits/{id}/assign-recount` - Assign products for recount
- `POST /api/inventory-audits/{id}/finalize-reconciliation` - Complete the audit

### Frontend Components

#### 1. **inventoryAuditView.vue** (Updated)
- Added "Reconcile Discrepancies" button (shows when 2+ participants have status='sent')
- Added `showReconciliationDialog()` method
- Added computed property `canShowReconciliation`

#### 2. **ReconciliationDialog.vue** (TO CREATE)
Location: `resources/js/Components/Apps/inventory/ReconciliationDialog.vue`

```vue
<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('close')"
    modal
    :style="{ width: '90vw', maxWidth: '1200px' }"
    :closable="true"
  >
    <template #header>
      <div class="tw-flex tw-items-center tw-gap-3">
        <i class="pi pi-check-circle tw-text-purple-500 tw-text-2xl"></i>
        <div>
          <h2 class="tw-text-xl tw-font-bold tw-text-gray-800">Reconciliation Report</h2>
          <p class="tw-text-sm tw-text-gray-500">
            {{ data.participants_sent }} participants • 
            {{ data.total_products }} products counted
          </p>
        </div>
      </div>
    </template>

    <!-- Summary Cards -->
    <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-mb-6">
      <!-- Consensus Card -->
      <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-4">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-check-circle tw-text-green-500 tw-text-3xl"></i>
          <div>
            <div class="tw-text-2xl tw-font-bold tw-text-green-700">
              {{ data.consensus_products }}
            </div>
            <div class="tw-text-sm tw-text-green-600">Products in Consensus</div>
            <div class="tw-text-xs tw-text-green-500">All participants agree</div>
          </div>
        </div>
      </div>

      <!-- Disputed Card -->
      <div class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-4">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-exclamation-triangle tw-text-amber-500 tw-text-3xl"></i>
          <div>
            <div class="tw-text-2xl tw-font-bold tw-text-amber-700">
              {{ data.disputed_products }}
            </div>
            <div class="tw-text-sm tw-text-amber-600">Disputed Products</div>
            <div class="tw-text-xs tw-text-amber-500">Recount required</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs for Consensus vs Disputed -->
    <TabView>
      <!-- Consensus Tab -->
      <TabPanel header="✓ Consensus Products">
        <DataTable
          :value="data.consensus"
          :paginator="true"
          :rows="10"
          responsiveLayout="scroll"
        >
          <Column field="product_id" header="Product ID" style="width: 100px" />
          <Column field="agreed_quantity" header="Agreed Quantity" style="width: 150px">
            <template #body="slotProps">
              <Tag :value="slotProps.data.agreed_quantity" severity="success" />
            </template>
          </Column>
          <Column header="Participants" style="min-width: 300px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-1">
                <Tag
                  v-for="p in slotProps.data.participant_counts"
                  :key="p.participant_id"
                  :value="`User ${p.participant_id}`"
                  severity="info"
                  icon="pi pi-user"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </TabPanel>

      <!-- Disputed Tab -->
      <TabPanel header="⚠ Disputed Products">
        <DataTable
          :value="data.discrepancies"
          v-model:selection="selectedDisputed"
          :paginator="true"
          :rows="10"
          selectionMode="multiple"
          dataKey="product_id"
          responsiveLayout="scroll"
        >
          <Column selectionMode="multiple" headerStyle="width: 3rem" />
          <Column field="product_id" header="Product ID" style="width: 100px" />
          <Column header="Counts" style="min-width: 400px">
            <template #body="slotProps">
              <div class="tw-space-y-2">
                <div
                  v-for="count in slotProps.data.participant_counts"
                  :key="count.participant_id"
                  class="tw-flex tw-items-center tw-justify-between tw-p-2 tw-bg-gray-50 tw-rounded"
                >
                  <span class="tw-font-semibold tw-text-gray-700">
                    <i class="pi pi-user tw-mr-2 tw-text-gray-400"></i>
                    User {{ count.participant_id }}
                  </span>
                  <Tag :value="count.actual_quantity" severity="warning" />
                </div>
              </div>
            </template>
          </Column>
          <Column field="variance" header="Variance" style="width: 120px">
            <template #body="slotProps">
              <Tag
                :value="`±${slotProps.data.variance}`"
                severity="danger"
                icon="pi pi-exclamation-triangle"
              />
            </template>
          </Column>
        </DataTable>

        <!-- Assign Recount Section -->
        <div v-if="selectedDisputed.length > 0" class="tw-mt-4 tw-p-4 tw-bg-purple-50 tw-rounded-lg tw-border tw-border-purple-200">
          <h3 class="tw-font-bold tw-text-purple-800 tw-mb-3">
            Assign {{ selectedDisputed.length }} products for recount
          </h3>
          <div class="tw-flex tw-gap-3">
            <Dropdown
              v-model="selectedParticipant"
              :options="participants"
              optionLabel="name"
              optionValue="id"
              placeholder="Select participant"
              class="tw-flex-1"
            />
            <Button
              label="Assign Recount"
              icon="pi pi-refresh"
              @click="assignRecount"
              :disabled="!selectedParticipant"
              class="tw-bg-purple-500 tw-text-white"
            />
          </div>
        </div>
      </TabPanel>
    </TabView>

    <template #footer>
      <div class="tw-flex tw-justify-between">
        <Button
          label="Close"
          icon="pi pi-times"
          @click="$emit('close')"
          class="p-button-text"
        />
        <Button
          v-if="data.disputed_products === 0"
          label="Finalize Audit"
          icon="pi pi-check"
          @click="$emit('finalize')"
          class="tw-bg-green-500 tw-text-white"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
  visible: Boolean,
  data: Object,
  participants: Array
});

const emit = defineEmits(['close', 'assign-recount', 'finalize']);

const selectedDisputed = ref([]);
const selectedParticipant = ref(null);

function assignRecount() {
  emit('assign-recount', {
    participant_id: selectedParticipant.value,
    product_ids: selectedDisputed.value.map(p => p.product_id)
  });
  selectedDisputed.value = [];
  selectedParticipant.value = null;
}
</script>
```

## 🔄 Complete Workflow

### Phase 1: Initial Counting
1. Supervisor creates audit and assigns participants
2. Participants count products independently
3. Each participant clicks "Send" when done
4. Their status changes to 'sent'

### Phase 2: Reconciliation Analysis
1. When 2+ participants have status='sent', "Reconcile Discrepancies" button appears
2. Supervisor clicks button
3. Backend analyzes all counts:
   - Groups products by product_id
   - Compares actual_quantity across participants
   - Returns consensus (all agree) vs discrepancies (different counts)

### Phase 3: Recount Assignment
1. Supervisor reviews reconciliation report
2. Selects disputed products
3. Assigns them to a participant for recount
4. Participant's status changes to 'recount'
5. Only disputed products appear in their view

### Phase 4: Recount Execution
1. Participant logs in and sees only disputed products
2. Recounts and updates quantities
3. Clicks "Send" again
4. Status changes back to 'sent'

### Phase 5: Finalization
1. Supervisor re-runs reconciliation
2. If all discrepancies resolved, clicks "Finalize Audit"
3. Audit status → 'completed'
4. All participants status → 'completed'
5. All products status → 'completed'

## 📊 Database Schema Updates

### inventory_audits_participantes
```sql
-- Add status column if not exists
ALTER TABLE inventory_audits_participantes 
ADD COLUMN status VARCHAR(50) DEFAULT 'draft';

-- Possible statuses:
-- 'draft' - Assigned but not started
-- 'in_progress' - Actively counting
-- 'sent' - Submitted counts
-- 'recount' - Assigned recount
-- 'completed' - Finalized
```

### inventory_audits_product
```sql
-- Add status column if not exists
ALTER TABLE inventory_audits_product 
ADD COLUMN status VARCHAR(50) DEFAULT 'pending';

-- Possible statuses:
-- 'pending' - Not yet counted
-- 'counted' - Has actual_quantity
-- 'recount_required' - Needs recount
-- 'completed' - Finalized
```

## 🎯 API Endpoints

### 1. Analyze Discrepancies
```http
GET /api/inventory-audits/{id}/analyze-discrepancies

Response:
{
  "can_reconcile": true,
  "participants_sent": 3,
  "total_products": 50,
  "consensus_products": 45,
  "disputed_products": 5,
  "consensus": [
    {
      "product_id": 2,
      "product_type": "stock",
      "agreed_quantity": 233,
      "participant_counts": [
        {"participant_id": 11, "actual_quantity": 233},
        {"participant_id": 12, "actual_quantity": 233}
      ]
    }
  ],
  "discrepancies": [
    {
      "product_id": 5,
      "product_type": "stock",
      "quantities": [100, 105, 98],
      "variance": 7,
      "participant_counts": [
        {"participant_id": 11, "actual_quantity": 100},
        {"participant_id": 12, "actual_quantity": 105},
        {"participant_id": 13, "actual_quantity": 98}
      ]
    }
  ]
}
```

### 2. Assign Recount
```http
POST /api/inventory-audits/{id}/assign-recount

Body:
{
  "participant_id": 11,
  "product_ids": [5, 7, 9]
}

Response:
{
  "message": "Recount assigned successfully",
  "data": {
    "success": true,
    "participant_id": 11,
    "products_to_recount": 3,
    "affected_records": 3
  }
}
```

### 3. Finalize Reconciliation
```http
POST /api/inventory-audits/{id}/finalize-reconciliation

Response:
{
  "success": true,
  "message": "Audit completed successfully",
  "completed_at": "2025-10-30 12:00:00"
}
```

## 🧪 Testing Steps

### 1. Create Test Audit
```php
php artisan tinker

$audit = \App\Models\Inventory\InventoryAudit::factory()->create();
$participants = \App\Models\User::limit(3)->get();

foreach ($participants as $p) {
    \App\Models\Inventory\InventoryAuditsParticipante::create([
        'inventory_audit_id' => $audit->id,
        'user_id' => $p->id,
        'is_participant' => true,
        'status' => 'sent'
    ]);
}
```

### 2. Create Test Counts
```php
// Participant 1 counts
DB::table('inventory_audits_product')->insert([
    'inventory_audit_id' => $audit->id,
    'product_id' => 2,
    'product_type' => 'stock',
    'participant_id' => 11,
    'theoretical_quantity' => 100,
    'actual_quantity' => 100,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Participant 2 counts (same)
DB::table('inventory_audits_product')->insert([
    'inventory_audit_id' => $audit->id,
    'product_id' => 2,
    'product_type' => 'stock',
    'participant_id' => 12,
    'theoretical_quantity' => 100,
    'actual_quantity' => 100, // Consensus
    'created_at' => now(),
    'updated_at' => now(),
]);

// Participant 2 counts product 5 differently
DB::table('inventory_audits_product')->insert([
    'inventory_audit_id' => $audit->id,
    'product_id' => 5,
    'product_type' => 'stock',
    'participant_id' => 11,
    'theoretical_quantity' => 100,
    'actual_quantity' => 98, // Different
    'created_at' => now(),
    'updated_at' => now(),
]);

DB::table('inventory_audits_product')->insert([
    'inventory_audit_id' => $audit->id,
    'product_id' => 5,
    'product_type' => 'stock',
    'participant_id' => 12,
    'theoretical_quantity' => 100,
    'actual_quantity' => 105, // Discrepancy!
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### 3. Test Analysis
```bash
curl -X GET http://localhost/api/inventory-audits/4/analyze-discrepancies
```

## 🔐 Permissions & Authorization
Add to your authorization logic:
```php
Gate::define('reconcile-audit', function (User $user, InventoryAudit $audit) {
    return $user->id === $audit->created_by || $user->hasRole('admin');
});
```

## 📝 Copilot Instructions Update

Add to `.github/copilot-instructions.md`:

```markdown
### Inventory Audit Reconciliation

When all participants submit counts (status='sent'):
1. Supervisor clicks "Reconcile Discrepancies"
2. System compares actual_quantity across participants
3. Products with matching counts = consensus
4. Products with different counts = disputed
5. Disputed products can be assigned for recount
6. Participant status changes to 'recount'
7. Only disputed products shown to that participant
8. Process repeats until all consensus
9. Supervisor finalizes audit → status='completed'

**Key Services**:
- `InventoryReconciliationService` - analysis and recount logic
- `InventoryAuditService` - original audit operations

**Status Flow**:
- Participant: draft → in_progress → sent → recount → sent → completed
- Product: pending → counted → recount_required → counted → completed
```

## 🚀 Next Steps

1. **Create ReconciliationDialog Component** (provided above)
2. **Run Migrations** if status columns don't exist
3. **Import Dialog in inventoryAuditView.vue**:
```vue
import ReconciliationDialog from '../../../Components/Apps/inventory/ReconciliationDialog.vue';
```

4. **Add Dialog to Template**:
```vue
<ReconciliationDialog
  v-if="showReconciliation"
  :visible="showReconciliation"
  :data="reconciliationData"
  :participants="audit.participants"
  @close="closeReconciliationDialog"
  @assign-recount="handleAssignRecount"
  @finalize="finalizeReconciliation"
/>
```

5. **Test Full Workflow**
6. **Add Loading States & Error Handling**
7. **Add Audit Logs** for compliance

## 📊 UI/UX Considerations

- **Color Coding**:
  - Green = Consensus
  - Amber = Disputed
  - Purple = Recount
  - Gray = Completed

- **Icons**:
  - ✓ = Consensus
  - ⚠ = Discrepancy
  - 🔄 = Recount
  - 🔒 = Locked/Completed

- **Notifications**:
  - Toast on successful recount assignment
  - Confirm dialog before finalization
  - Warning if trying to finalize with disputes

---

**Implementation Status**: ✅ Backend Complete | ⏳ Frontend Component Needed

**Estimated Completion**: 2-3 hours for full integration and testing
