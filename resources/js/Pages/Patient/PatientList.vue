<script setup>
import { ref, onMounted, computed } from 'vue';
import PatientListItem from './PatientListItem.vue';

onMounted(() => {
    // Component initialization if needed
});
</script>


<template>
    <div class="patient-list-root">
        <div class="appointment-page">
            <div class="container-fluid">
                <div class="row ">
                    <div class="col-sm-6">
                        <h1 class="m-0">Patients</h1> </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Patient</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <PatientListItem />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.appointment-page {
    padding: 20px;
}

/* Add styling for pagination if needed */
:deep(.pagination) {
    margin-bottom: 0;
    display: flex; /* Ensures proper alignment */
    justify-content: center; /* Center pagination */
    margin-top: 20px; /* Add some space above pagination */
}

:deep(.page-link) {
    color: #007bff;
    background-color: #fff;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem; /* Adjust padding */
    border-radius: 5px; /* Slightly rounded corners */
    margin: 0 2px; /* Small gap between pages */
    transition: all 0.2s ease-in-out;
}

:deep(.page-link:hover) {
    background-color: #e9ecef; /* Light hover background */
    border-color: #007bff;
}

:deep(.page-item.active .page-link) {
    background-color: #007bff;
    border-color: #007bff;
    color: #fff;
    box-shadow: 0 2px 5px rgba(0, 123, 255, 0.2); /* Subtle shadow for active page */
}

:deep(.page-item.disabled .page-link) {
    color: #6c757d;
    pointer-events: none;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
</style>
