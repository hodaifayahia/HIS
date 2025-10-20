<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    Doctor Schedule Management Demo
                </h1>
                <p class="mt-2 text-gray-600">
                    Test and validate the enhanced schedule management system with comprehensive scenarios
                </p>
            </div>

            <!-- Test Controls -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Test Controls</h2>
                
                <div class="flex flex-wrap gap-4">
                    <Button 
                        @click="runValidationTests" 
                        :loading="testingValidation"
                        icon="pi pi-check-circle"
                        class="p-button-success"
                    >
                        Run Validation Tests
                    </Button>
                    
                    <Button 
                        @click="runScenarioTests" 
                        :loading="testingScenarios"
                        icon="pi pi-users"
                        class="p-button-info"
                    >
                        Test Real-World Scenarios
                    </Button>
                    
                    <Button 
                        @click="clearResults" 
                        icon="pi pi-trash"
                        class="p-button-secondary"
                    >
                        Clear Results
                    </Button>
                    
                    <Button 
                        @click="openScheduleManager" 
                        icon="pi pi-calendar"
                        class="p-button-primary"
                    >
                        Open Schedule Manager
                    </Button>
                </div>
            </div>

            <!-- Test Results -->
            <div v-if="testResults.length > 0" class="space-y-6">
                <div 
                    v-for="(result, index) in testResults" 
                    :key="index"
                    class="bg-white rounded-lg shadow-sm border"
                >
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ result.title }}
                            </h3>
                            <div class="flex items-center space-x-2">
                                <Badge 
                                    :value="`${result.passed}/${result.total}`" 
                                    :severity="result.passed === result.total ? 'success' : 'danger'"
                                />
                                <span class="text-sm text-gray-500">
                                    {{ ((result.passed / result.total) * 100).toFixed(1) }}% Success
                                </span>
                            </div>
                        </div>

                        <!-- Summary Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ result.passed }}</div>
                                <div class="text-sm text-green-700">Passed</div>
                            </div>
                            <div class="text-center p-4 bg-red-50 rounded-lg">
                                <div class="text-2xl font-bold text-red-600">{{ result.failed }}</div>
                                <div class="text-sm text-red-700">Failed</div>
                            </div>
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ result.total }}</div>
                                <div class="text-sm text-blue-700">Total</div>
                            </div>
                        </div>

                        <!-- Detailed Results -->
                        <Accordion v-if="result.details && result.details.length > 0">
                            <AccordionTab header="Detailed Results">
                                <div class="space-y-2">
                                    <div 
                                        v-for="(detail, detailIndex) in result.details" 
                                        :key="detailIndex"
                                        class="flex items-center justify-between p-3 rounded-lg"
                                        :class="detail.passed ? 'bg-green-50' : 'bg-red-50'"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <i 
                                                :class="detail.passed ? 'pi pi-check text-green-600' : 'pi pi-times text-red-600'"
                                            ></i>
                                            <span class="font-medium">{{ detail.name }}</span>
                                        </div>
                                        <div v-if="detail.error" class="text-sm text-red-600">
                                            {{ detail.error }}
                                        </div>
                                    </div>
                                </div>
                            </AccordionTab>
                        </Accordion>

                        <!-- Scenario Results -->
                        <div v-if="result.scenarios" class="mt-6">
                            <h4 class="text-md font-semibold text-gray-900 mb-3">Scenario Results</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div 
                                    v-for="scenario in result.scenarios" 
                                    :key="scenario.name"
                                    class="p-4 rounded-lg border"
                                    :class="scenario.passed ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <h5 class="font-medium capitalize">{{ scenario.name.replace(/([A-Z])/g, ' $1') }}</h5>
                                        <i 
                                            :class="scenario.passed ? 'pi pi-check text-green-600' : 'pi pi-times text-red-600'"
                                        ></i>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-2">
                                        {{ scenario.scheduleCount }} schedule(s)
                                    </div>
                                    <div v-if="scenario.errors && scenario.errors.length > 0" class="text-sm text-red-600">
                                        <ul class="list-disc list-inside">
                                            <li v-for="error in scenario.errors" :key="error">{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Schedule Data -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mt-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Sample Schedule Data</h2>
                
                <Accordion>
                    <AccordionTab header="General Practitioner Schedule">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-blue-50 rounded-lg">
                                    <h4 class="font-medium text-blue-900">Monday Morning</h4>
                                    <p class="text-sm text-blue-700">08:00 - 12:00 (16 patients)</p>
                                    <p class="text-sm text-blue-600">Break: 10:00 - 10:30</p>
                                </div>
                                <div class="p-4 bg-blue-50 rounded-lg">
                                    <h4 class="font-medium text-blue-900">Monday Afternoon</h4>
                                    <p class="text-sm text-blue-700">14:00 - 18:00 (16 patients)</p>
                                    <p class="text-sm text-blue-600">Break: 16:00 - 16:30</p>
                                </div>
                            </div>
                        </div>
                    </AccordionTab>
                    
                    <AccordionTab header="Specialist Schedule">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-green-50 rounded-lg">
                                    <h4 class="font-medium text-green-900">Tuesday Morning</h4>
                                    <p class="text-sm text-green-700">09:00 - 13:00 (12 patients)</p>
                                    <p class="text-sm text-green-600">Break: 11:00 - 12:00</p>
                                </div>
                                <div class="p-4 bg-green-50 rounded-lg">
                                    <h4 class="font-medium text-green-900">Thursday Afternoon</h4>
                                    <p class="text-sm text-green-700">15:00 - 19:00 (12 patients)</p>
                                    <p class="text-sm text-green-600">Break: 17:00 - 17:30</p>
                                </div>
                            </div>
                        </div>
                    </AccordionTab>
                    
                    <AccordionTab header="Emergency Doctor Schedule">
                        <div class="p-4 bg-red-50 rounded-lg">
                            <h4 class="font-medium text-red-900">February 15, 2024</h4>
                            <p class="text-sm text-red-700">06:00 - 14:00 (24 patients)</p>
                            <p class="text-sm text-red-600">Breaks: 09:00-09:30, 12:00-12:30</p>
                        </div>
                    </AccordionTab>
                </Accordion>
            </div>

            <!-- Console Output -->
            <div v-if="consoleOutput.length > 0" class="bg-gray-900 text-green-400 rounded-lg p-6 mt-8 font-mono text-sm">
                <h2 class="text-white text-lg font-semibold mb-4">Console Output</h2>
                <div class="space-y-1 max-h-96 overflow-y-auto">
                    <div v-for="(line, index) in consoleOutput" :key="index">
                        {{ line }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Accordion from 'primevue/accordion'
import AccordionTab from 'primevue/accordiontab'

// Import test utilities
import { ScheduleValidator } from './ScheduleValidation.js'
import ScheduleTestScenarios from './ScheduleTestScenarios.js'

const router = useRouter()

// Reactive data
const testResults = ref([])
const consoleOutput = ref([])
const testingValidation = ref(false)
const testingScenarios = ref(false)

// Override console.log to capture output
const originalConsoleLog = console.log
const captureConsoleOutput = (capture = true) => {
    if (capture) {
        console.log = (...args) => {
            consoleOutput.value.push(args.join(' '))
            originalConsoleLog(...args)
        }
    } else {
        console.log = originalConsoleLog
    }
}

// Test methods
const runValidationTests = async () => {
    testingValidation.value = true
    consoleOutput.value = []
    
    try {
        captureConsoleOutput(true)
        const results = ScheduleTestScenarios.runAllTests()
        captureConsoleOutput(false)
        
        testResults.value.unshift({
            title: 'Validation Tests',
            timestamp: new Date().toLocaleString(),
            passed: results.passed,
            failed: results.failed,
            total: results.total,
            details: results.details
        })
    } catch (error) {
        console.error('Error running validation tests:', error)
    } finally {
        testingValidation.value = false
    }
}

const runScenarioTests = async () => {
    testingScenarios.value = true
    consoleOutput.value = []
    
    try {
        captureConsoleOutput(true)
        const results = ScheduleTestScenarios.testRealWorldScenarios()
        captureConsoleOutput(false)
        
        testResults.value.unshift({
            title: 'Real-World Scenarios',
            timestamp: new Date().toLocaleString(),
            passed: results.passed,
            failed: results.failed,
            total: results.total,
            scenarios: results.scenarios
        })
    } catch (error) {
        console.error('Error running scenario tests:', error)
    } finally {
        testingScenarios.value = false
    }
}

const clearResults = () => {
    testResults.value = []
    consoleOutput.value = []
}

const openScheduleManager = () => {
    router.push({ name: 'doctor.schedule.management' })
}

// Demo data for manual testing
const createSampleSchedule = () => {
    return {
        doctor_id: 1,
        day_of_week: 'monday',
        shift_period: 'morning',
        start_time: '08:00',
        end_time: '12:00',
        number_of_patients_per_day: 16,
        break_duration: 30,
        break_times: JSON.stringify([{
            start: '10:00',
            end: '10:30'
        }])
    }
}

// Run a quick validation test on mount
onMounted(() => {
    // Make test utilities available globally for console testing
    window.ScheduleValidator = ScheduleValidator
    window.ScheduleTestScenarios = ScheduleTestScenarios
    
    console.log('🧪 Schedule Management Demo loaded!')
    console.log('Available testing utilities:')
    console.log('- window.ScheduleValidator')
    console.log('- window.ScheduleTestScenarios')
    console.log('- Try: ScheduleTestScenarios.runAllTests()')
})
</script>

<style scoped>
/* Custom styles for the demo component */
.p-accordion .p-accordion-header-link {
    @apply font-medium;
}

.p-accordion .p-accordion-content {
    @apply pt-4;
}

/* Console output styling */
.font-mono {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}

/* Responsive grid adjustments */
@media (max-width: 768px) {
    .grid-cols-3 {
        @apply grid-cols-1;
    }
    
    .md\:grid-cols-2 {
        @apply grid-cols-1;
    }
}
</style>