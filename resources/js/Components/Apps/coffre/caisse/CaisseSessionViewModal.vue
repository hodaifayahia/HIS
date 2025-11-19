<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="pi pi-eye"></i>
          Session Details
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <div v-if="!session">
          <p>Loading…</p>
        </div>

        <div v-else>
          <div class="grid">
            <div class="col-12">
              <h5>{{ session.caisse?.name ?? 'Caisse #' + session.caisse_id }}</h5>
              <small class="text-muted">{{ session.caisse?.location ?? '' }}</small>
            </div>

            <div class="col-6">
              <p><strong>Session ID:</strong> {{ session.id }}</p>
              <p><strong>Status:</strong> {{ session.status }}</p>
              <p><strong>Opened at:</strong> {{ formatDate(session.ouverture_at) }}</p>
              <p><strong>Opened by:</strong> {{ session.opened_by?.name ?? session.open_by ?? '—' }}</p>
            </div>

            <div class="col-6">
              <p><strong>Opening amount:</strong> {{ formatCurrency(session.opening_amount) }}</p>
              <p><strong>Closed at:</strong> {{ formatDate(session.cloture_at) }}</p>
              <p><strong>Closed by:</strong> {{ session.closed_by?.name ?? session.closed_by ?? '—' }}</p>
              <p><strong>Closing amount:</strong> {{ session.closing_amount ? formatCurrency(session.closing_amount) : '—' }}</p>
            </div>

            <div class="col-12 mt-3">
              <h6>Source Coffre</h6>
              <div v-if="session.source_coffre">
                <p><strong>{{ session.source_coffre.name }}</strong> — {{ session.source_coffre.location }}</p>
              </div>
              <div v-else class="text-muted">No source coffre</div>
            </div>

            <div class="col-12 mt-3">
              <h6>Denominations</h6>
              <table class="p-datatable">
                <thead>
                  <tr>
                    <th>Type</th><th>Value</th><th>Quantity</th><th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in session.denominations ?? []" :key="d.id">
                    <td>{{ d.denomination_type }}</td>
                    <td>{{ d.value }}</td>
                    <td>{{ d.quantity }}</td>
                    <td>{{ formatCurrency(d.total_amount) }}</td>
                  </tr>
                  <tr v-if="!(session.denominations && session.denominations.length)">
                    <td colspan="4" class="text-muted">No denominations recorded</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="col-12 mt-3">
              <h6>Notes</h6>
              <p><strong>Opening:</strong> {{ session.opening_notes ?? '—' }}</p>
              <p><strong>Closing:</strong> {{ session.closing_notes ?? '—' }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <Button label="Close" icon="pi pi-times" class="p-button-secondary" @click="$emit('close')" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from 'primevue/button';

const props = defineProps({
  session: { type: Object, default: null }
});

const formatCurrency = (v) => {
  if (v === null || v === undefined) return '—';
  return new Intl.NumberFormat('fr-DZ', { style: 'currency', currency: 'DZD', minimumFractionDigits: 2 }).format(v);
};

const formatDate = (d) => {
  if (!d) return '—';
  try { return new Date(d).toLocaleString(); } catch { return d; }
};
</script>

<style scoped>
/* reuse modal styles in project */
.modal-overlay { position: fixed; inset:0; background: rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:1050; }
.modal-content { background:white; width:90%; max-width:800px; border-radius:12px; overflow:auto; }
.modal-header { padding:1rem; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; background:#f5f7fb; }
.modal-body { padding:1rem; }
.modal-footer { padding:1rem; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:.5rem; }
.btn-close { background:none; border:none; font-size:1.1rem; cursor:pointer; }
</style>