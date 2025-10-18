<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    fournisseurData: {
        type: Object,
        default: () => ({
            id: null,
            company_name: '',
            contact_person: '',
            email: '',
            phone: '',
            address: '',
            city: '',
            country: '',
            tax_id: '',
            website: '',
            notes: '',
            is_active: true,
            contacts: []
        })
    }
});

const emit = defineEmits(['close', 'fournisseur-saved']);
const toast = useToast();
const confirm = useConfirm();

// Reactive data
const loading = ref(false);
const saving = ref(false);
const isEdit = ref(false);

const fournisseur = ref({
    id: null,
    company_name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: '',
    tax_id: '',
    website: '',
    notes: '',
    is_active: true,
    contacts: []
});

const contactDialog = ref(false);
const editingContact = ref(null);
const contactForm = ref({
    id: null,
    name: '',
    position: '',
    email: '',
    phone: '',
    mobile: '',
    is_primary: false
});

// Watch for prop changes
watch(() => props.fournisseurData, (newVal) => {
    if (newVal && Object.keys(newVal).length > 0) {
        fournisseur.value = { ...newVal };
        isEdit.value = !!newVal.id;
    } else {
        resetForm();
        isEdit.value = false;
    }
}, { immediate: true });

const resetForm = () => {
    fournisseur.value = {
        id: null,
        company_name: '',
        contact_person: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        country: '',
        tax_id: '',
        website: '',
        notes: '',
        is_active: true,
        contacts: []
    };
};

// Methods
const saveFournisseur = async () => {
    saving.value = true;
    try {
        const data = { ...fournisseur.value };

        if (isEdit.value) {
            await axios.put(`/api/fournisseurs/${fournisseur.value.id}`, data);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Supplier updated successfully',
                life: 3000
            });
        } else {
            await axios.post('/api/fournisseurs', data);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Supplier created successfully',
                life: 3000
            });
        }

        emit('fournisseur-saved');
        closeModal();
    } catch (error) {
        console.error('Error saving fournisseur:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to save supplier',
            life: 3000
        });
    } finally {
        saving.value = false;
    }
};

const closeModal = () => {
    resetForm();
    emit('close');
};

// Contact management methods
const openContactDialog = (contact = null) => {
    editingContact.value = contact;
    if (contact) {
        contactForm.value = { ...contact };
    } else {
        contactForm.value = {
            id: null,
            name: '',
            position: '',
            email: '',
            phone: '',
            mobile: '',
            is_primary: false
        };
    }
    contactDialog.value = true;
};

const saveContact = () => {
    if (editingContact.value) {
        // Update existing contact
        const index = fournisseur.value.contacts.findIndex(c => c.id === editingContact.value.id);
        if (index !== -1) {
            fournisseur.value.contacts[index] = { ...contactForm.value };
        }
    } else {
        // Add new contact
        fournisseur.value.contacts.push({ ...contactForm.value });
    }

    contactDialog.value = false;
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Contact saved successfully',
        life: 3000
    });
};

const deleteContact = (contact) => {
    confirm.require({
        message: `Are you sure you want to delete contact ${contact.name}?`,
        header: 'Delete Contact',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'tw-p-3 tw-text-gray-600',
        acceptClass: 'tw-p-3 tw-bg-red-600 tw-text-white',
        accept: () => {
            const index = fournisseur.value.contacts.findIndex(c => c.id === contact.id || c === contact);
            if (index !== -1) {
                fournisseur.value.contacts.splice(index, 1);
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Contact deleted successfully',
                    life: 3000
                });
            }
        }
    });
};

const setPrimaryContact = (contact) => {
    fournisseur.value.contacts.forEach(c => {
        c.is_primary = (c.id === contact.id || c === contact);
    });
};
</script>

<template>
    <teleport to="body">
        <transition name="modal-fade">
            <div v-if="showModal" class="tw-fixed tw-inset-0 tw-bg-gradient-to-br tw-from-gray-900/80 tw-to-gray-600/60 tw-backdrop-blur-sm tw-flex tw-justify-center tw-items-center tw-z-50 tw-p-4">
                <transition name="modal-scale" appear>
                    <div class="tw-bg-gradient-to-br tw-from-white tw-to-slate-50 tw-rounded-xl tw-shadow-2xl tw-shadow-black/20 tw-border tw-border-white/10 tw-w-full tw-max-w-6xl tw-max-h-[90vh] tw-overflow-hidden tw-relative">
                        <!-- Header -->
                        <div class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-slate-200 tw-p-8 tw-border-b tw-border-slate-200 tw-relative">
                            <div class="tw-flex tw-items-center tw-gap-4">
                                <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-700 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-xl tw-shadow-lg tw-shadow-blue-500/30">
                                    <i class="pi pi-building"></i>
                                </div>
                                <div>
                                    <h2 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-m-0">
                                        {{ isEdit ? 'Edit Supplier' : 'Add New Supplier' }}
                                    </h2>
                                    <p class="tw-text-gray-600 tw-text-sm tw-m-0 tw-mt-1">
                                        {{ isEdit ? 'Update supplier information' : 'Create a new supplier in your database' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="closeModal"
                                class="tw-absolute tw-top-4 tw-right-4 tw-w-10 tw-h-10 tw-border-none tw-bg-white/90 tw-rounded-lg tw-text-gray-600 tw-cursor-pointer tw-transition-all tw-duration-300 hover:tw-bg-red-50 hover:tw-text-red-600 hover:tw-scale-110 tw-flex tw-items-center tw-justify-center tw-text-lg tw-backdrop-blur-sm"
                            >
                                <i class="pi pi-times"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="tw-p-8 tw-max-h-[calc(90vh-8rem)] tw-overflow-y-auto">
                            <form @submit.prevent="saveFournisseur" class="tw-space-y-6">
                                <!-- Company Information Section -->
                                <div class="tw-grid tw-grid-cols-1 tw-lg:grid-cols-2 tw-gap-8">
                                    <!-- Left Column -->
                                    <div class="tw-space-y-6">
                                        <!-- Company Name -->
                                        <div>
                                            <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                <i class="pi pi-building tw-text-gray-500"></i>
                                                Company Name
                                                <span class="tw-text-red-500 tw-font-bold">*</span>
                                            </label>
                                            <InputText
                                                v-model="fournisseur.company_name"
                                                placeholder="Enter company name"
                                                class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                required
                                            />
                                        </div>

                                        <!-- Contact Person & Email Row -->
                                        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
                                            <div>
                                                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                    <i class="pi pi-user tw-text-gray-500"></i>
                                                    Contact Person
                                                </label>
                                                <InputText
                                                    v-model="fournisseur.contact_person"
                                                    placeholder="Enter contact person"
                                                    class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                    <i class="pi pi-envelope tw-text-gray-500"></i>
                                                    Email
                                                </label>
                                                <InputText
                                                    v-model="fournisseur.email"
                                                    type="email"
                                                    placeholder="Enter email address"
                                                    class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                />
                                            </div>
                                        </div>

                                        <!-- Phone & Website Row -->
                                        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
                                            <div>
                                                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                    <i class="pi pi-phone tw-text-gray-500"></i>
                                                    Phone
                                                </label>
                                                <InputText
                                                    v-model="fournisseur.phone"
                                                    placeholder="Enter phone number"
                                                    class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                    <i class="pi pi-globe tw-text-gray-500"></i>
                                                    Website
                                                </label>
                                                <InputText
                                                    v-model="fournisseur.website"
                                                    type="url"
                                                    placeholder="https://example.com"
                                                    class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                />
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div>
                                            <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                <i class="pi pi-map-marker tw-text-gray-500"></i>
                                                Address
                                            </label>
                                            <Textarea
                                                v-model="fournisseur.address"
                                                placeholder="Enter full address"
                                                rows="3"
                                                class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white tw-resize-none"
                                            />
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="tw-space-y-6">
                                        <!-- City, Country, Tax ID Row -->
                                        <div class="tw-grid tw-grid-cols-3 tw-gap-4">
                                            <div>
                                                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                    <i class="pi pi-map tw-text-gray-500"></i>
                                                    City
                                                </label>
                                                <InputText
                                                    v-model="fournisseur.city"
                                                    placeholder="City"
                                                    class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                    <i class="pi pi-flag tw-text-gray-500"></i>
                                                    Country
                                                </label>
                                                <InputText
                                                    v-model="fournisseur.country"
                                                    placeholder="Country"
                                                    class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                    <i class="pi pi-credit-card tw-text-gray-500"></i>
                                                    Tax ID
                                                </label>
                                                <InputText
                                                    v-model="fournisseur.tax_id"
                                                    placeholder="Tax ID"
                                                    class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white"
                                                />
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div>
                                            <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                                                <i class="pi pi-sticky-note tw-text-gray-500"></i>
                                                Notes
                                            </label>
                                            <Textarea
                                                v-model="fournisseur.notes"
                                                placeholder="Additional notes about the supplier"
                                                rows="4"
                                                class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-transition-all tw-duration-300 focus:tw-border-blue-500 focus:tw-shadow-lg focus:tw-shadow-blue-500/10 focus:tw--translate-y-0.5 tw-bg-white tw-resize-none"
                                            />
                                        </div>

                                        <!-- Active Status -->
                                        <div>
                                            <label class="tw-flex tw-items-center tw-gap-3 tw-cursor-pointer">
                                                <div class="tw-relative">
                                                    <input
                                                        v-model="fournisseur.is_active"
                                                        type="checkbox"
                                                        class="tw-sr-only"
                                                    />
                                                    <div class="tw-w-12 tw-h-6 tw-bg-gray-300 tw-rounded-full tw-transition-all tw-duration-300" :class="{ 'tw-bg-gradient-to-r tw-from-green-400 tw-to-green-600': fournisseur.is_active }">
                                                        <div class="tw-absolute tw-top-0.5 tw-left-0.5 tw-w-5 tw-h-5 tw-bg-white tw-rounded-full tw-shadow-md tw-transition-all tw-duration-300" :class="{ 'tw-translate-x-6': fournisseur.is_active }"></div>
                                                    </div>
                                                </div>
                                                <span class="tw-text-sm tw-font-semibold tw-text-gray-700">Active Supplier</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contacts Section -->
                                <div class="tw-border-t tw-border-gray-200 tw-pt-6">
                                    <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                                        <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-flex tw-items-center tw-gap-2">
                                            <i class="pi pi-users tw-text-blue-600"></i>
                                            Contacts
                                        </h3>
                                        <Button
                                            label="Add Contact"
                                            icon="pi pi-plus"
                                            class="tw-bg-blue-600 tw-border-blue-600 tw-hover:bg-blue-700 tw-px-4 tw-py-2 tw-text-sm"
                                            @click="openContactDialog()"
                                        />
                                    </div>

                                    <div v-if="fournisseur.contacts.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
                                        <i class="pi pi-users tw-text-4xl tw-mb-4 tw-block"></i>
                                        <p class="tw-text-lg tw-font-medium">No contacts added yet</p>
                                        <p class="tw-text-sm">Click "Add Contact" to add supplier contacts</p>
                                    </div>

                                    <div v-else class="tw-space-y-3">
                                        <div
                                            v-for="(contact, index) in fournisseur.contacts"
                                            :key="contact.id || index"
                                            class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-gray-50 tw-rounded-lg tw-border tw-border-gray-200 hover:tw-bg-gray-100 tw-transition-colors"
                                        >
                                            <div class="tw-flex tw-items-center tw-gap-3">
                                                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                                                    <i class="pi pi-user tw-text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <p class="tw-font-medium tw-text-gray-900">
                                                        {{ contact.name }}
                                                        <span v-if="contact.is_primary" class="tw-ml-2 tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-medium tw-bg-green-100 tw-text-green-800">
                                                            Primary
                                                        </span>
                                                    </p>
                                                    <p class="tw-text-sm tw-text-gray-600">{{ contact.position || 'No position' }}</p>
                                                </div>
                                            </div>
                                            <div class="tw-flex tw-items-center tw-gap-2">
                                                <Button
                                                    icon="pi pi-star"
                                                    :class="contact.is_primary ? 'tw-text-yellow-500' : 'tw-text-gray-400'"
                                                    class="tw-p-2 hover:tw-bg-yellow-50 tw-rounded-lg"
                                                    v-tooltip.top="'Set as Primary'"
                                                    @click="setPrimaryContact(contact)"
                                                />
                                                <Button
                                                    icon="pi pi-pencil"
                                                    class="tw-p-2 tw-text-blue-600 hover:tw-bg-blue-50 tw-rounded-lg"
                                                    v-tooltip.top="'Edit Contact'"
                                                    @click="openContactDialog(contact)"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    class="tw-p-2 tw-text-red-600 hover:tw-bg-red-50 tw-rounded-lg"
                                                    v-tooltip.top="'Delete Contact'"
                                                    @click="deleteContact(contact)"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Footer -->
                        <div class="tw-flex tw-justify-end tw-gap-3 tw-p-6 tw-border-t tw-border-gray-200 tw-bg-gray-50">
                            <Button
                                label="Cancel"
                                icon="pi pi-times"
                                class="tw-bg-gray-600 tw-border-gray-600 tw-hover:bg-gray-700 tw-px-6 tw-py-2"
                                @click="closeModal"
                            />
                            <Button
                                :label="isEdit ? 'Update Supplier' : 'Create Supplier'"
                                icon="pi pi-save"
                                :loading="saving"
                                class="tw-bg-blue-600 tw-border-blue-600 tw-hover:bg-blue-700 tw-px-6 tw-py-2"
                                @click="saveFournisseur"
                            />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>

        <!-- Contact Dialog -->
        <Dialog
            :visible="contactDialog"
            @update:visible="contactDialog = $event"
            :header="editingContact ? 'Edit Contact' : 'Add Contact'"
            modal
            class="tw-w-full tw-max-w-md"
        >
            <div class="tw-space-y-4">
                <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                        Name *
                    </label>
                    <InputText
                        v-model="contactForm.name"
                        placeholder="Enter contact name"
                        class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm"
                        required
                    />
                </div>

                <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                        Position
                    </label>
                    <InputText
                        v-model="contactForm.position"
                        placeholder="Enter position/title"
                        class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm"
                    />
                </div>

                <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                        Email
                    </label>
                    <InputText
                        v-model="contactForm.email"
                        type="email"
                        placeholder="Enter email address"
                        class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm"
                    />
                </div>

                <div class="tw-grid tw-grid-cols-2 tw-gap-4">
                    <div>
                        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                            Phone
                        </label>
                        <InputText
                            v-model="contactForm.phone"
                            placeholder="Enter phone"
                            class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm"
                        />
                    </div>
                    <div>
                        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                            Mobile
                        </label>
                        <InputText
                            v-model="contactForm.mobile"
                            placeholder="Enter mobile"
                            class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm"
                        />
                    </div>
                </div>

                <div class="tw-flex tw-items-center tw-gap-3">
                    <Checkbox
                        v-model="contactForm.is_primary"
                        inputId="is_primary"
                    />
                    <label for="is_primary" class="tw-text-sm tw-font-semibold tw-text-gray-700">
                        Set as Primary Contact
                    </label>
                </div>
            </div>

            <template #footer>
                <div class="tw-flex tw-justify-end tw-gap-2">
                    <Button
                        label="Cancel"
                        icon="pi pi-times"
                        class="tw-bg-gray-600 tw-border-gray-600"
                        @click="contactDialog = false"
                    />
                    <Button
                        label="Save Contact"
                        icon="pi pi-save"
                        class="tw-bg-blue-600 tw-border-blue-600"
                        @click="saveContact"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Confirm Dialog -->
        <ConfirmDialog />
    </teleport>
</template>

<style scoped>
/* Enhanced Modal Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    transform: scale(0.9) translateY(-20px);
}

/* Enhanced Modal Overlay */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.8), rgba(55, 65, 81, 0.6));
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 50;
    padding: 1rem;
}

/* Enhanced Modal Container */
.modal-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 1rem;
    box-shadow:
        0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04),
        0 0 0 1px rgba(255, 255, 255, 0.1);
    width: 100%;
    max-width: 42rem;
    max-height: 90vh;
    overflow: hidden;
    position: relative;
}

.modal-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
}

/* Enhanced Header */
.modal-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 2rem;
    border-bottom: 1px solid #e2e8f0;
    position: relative;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.modal-icon {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.modal-subtitle {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0.25rem 0 0 0;
}

.modal-close-button {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 2.5rem;
    height: 2.5rem;
    border: none;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 0.5rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    backdrop-filter: blur(4px);
}

.modal-close-button:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    transform: scale(1.1);
}

/* Enhanced Body */
.modal-body {
    padding: 2rem;
    max-height: calc(90vh - 8rem);
    overflow-y: auto;
}

/* Enhanced Form Groups */
.form-group {
    position: relative;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.label-icon {
    color: #6b7280;
    font-size: 0.875rem;
}

.required-star {
    color: #ef4444;
    font-weight: 700;
}

/* Enhanced Form Inputs */
.form-input,
.form-textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: #9ca3af;
}

.input-error {
    border-color: #ef4444;
}

.input-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Enhanced Switch */
.switch-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.25rem;
}

.switch-input {
    display: none; /* Keep this hidden */
}

.switch-label {
    position: relative;
    width: 3rem;
    height: 1.5rem;
    background: #d1d5db;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.switch-slider {
    position: absolute;
    top: 0.125rem;
    left: 0.125rem;
    width: 1.25rem;
    height: 1.25rem;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* These are the crucial CSS rules for the visual switch */
.switch-input:checked + .switch-label {
    background: linear-gradient(135deg, #10b981, #059669);
}

.switch-input:checked + .switch-label .switch-slider {
    transform: translateX(1.5rem);
}

.switch-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

/* Enhanced Error Messages */
.error-message {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 0.25rem;
    border-left: 3px solid #ef4444;
}

/* Enhanced Footer */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5rem;
}

/* Enhanced Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #e5e7eb, #d1d5db);
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 640px) {
    .modal-overlay {
        padding: 0.5rem;
    }

    .modal-header,
    .modal-body {
        padding: 1.5rem;
    }

    .modal-title {
        font-size: 1.25rem;
    }

    .modal-footer {
        padding-top: 1rem;
        margin-top: 1rem;
    }

    .btn {
        padding: 0.625rem 1.25rem;
        font-size: 0.8125rem;
    }
}

/* Custom Scrollbar */
.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Form specific styles */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

/* Contact list styles */
.contact-item {
    transition: all 0.3s ease;
}

.contact-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Dialog specific styles */
:deep(.p-dialog) {
    border-radius: 0.75rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-bottom: 1px solid #e2e8f0;
    padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-footer) {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}
</style>